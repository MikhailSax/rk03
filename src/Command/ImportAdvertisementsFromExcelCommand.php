<?php

namespace App\Command;

use App\Entity\Advertisement;
use App\Entity\AdvertisementSide;
use App\Entity\AdvertisementType;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:ads-from-excel',
    description: 'Импортирует конструкции из Excel-файла в advertisement/advertisement_side'
)]
class ImportAdvertisementsFromExcelCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Путь до Excel-файла (.xlsx/.xls).')
            ->setHelp(<<<'HELP'
Если путь к файлу не передан, команда попробует взять файл list.xlsx из корня проекта.

Примеры:
  php bin/console app:import:ads-from-excel
  php bin/console app:import:ads-from-excel /full/path/to/file.xlsx
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = trim((string) ($input->getArgument('file') ?? ''));
        if ($file === '') {
            $file = dirname(__DIR__, 2) . '/list.xlsx';
            $io->note(sprintf('Путь к файлу не передан, используется файл по умолчанию: %s', $file));
        }

        if (!is_file($file)) {
            $io->error(sprintf('Файл не найден: %s', $file));
            return Command::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $headerRow = null;
        $headers = [];

        for ($row = 1; $row <= min($highestRow, 30); $row++) {
            $current = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row)->getFormattedValue());
                if ($value !== '') {
                    $current[$this->normalizeHeader($value)] = $column;
                }
            }

            if (isset($current['номер'], $current['сторона'], $current['типконструкции'])) {
                $headerRow = $row;
                $headers = $current;
                break;
            }
        }

        if ($headerRow === null) {
            $io->error('Не найдена строка заголовка. Ожидаются как минимум колонки: Номер, Сторона, Тип конструкции.');
            return Command::FAILURE;
        }

        $typeRepo = $this->em->getRepository(AdvertisementType::class);
        $adRepo = $this->em->getRepository(Advertisement::class);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $placeNumber = $this->readCell($sheet, $row, $headers, 'номер');
            $sideCode = mb_strtoupper($this->readCell($sheet, $row, $headers, 'сторона'));
            $typeName = $this->readCell($sheet, $row, $headers, 'типконструкции');

            if ($placeNumber === '' || $sideCode === '' || $typeName === '') {
                continue;
            }

            $type = $typeRepo->findOneBy(['name' => $typeName]);
            if (!$type instanceof AdvertisementType) {
                $io->warning(sprintf('Строка %d: тип "%s" не найден в БД, запись пропущена.', $row, $typeName));
                $skipped++;
                continue;
            }

            $ad = $adRepo->findOneBy(['placeNumber' => $placeNumber]);
            $isNew = false;

            if (!$ad instanceof Advertisement) {
                $ad = new Advertisement();
                $ad->setPlaceNumber($placeNumber);
                $ad->setCode($placeNumber);
                $isNew = true;
            }

            $ad->setType($type);
            $ad->setAddress($this->emptyToNull($this->readCell($sheet, $row, $headers, 'адрес')));

            [$latitude, $longitude] = $this->parseCoordinates($this->readCell($sheet, $row, $headers, 'координаты'));
            if ($latitude !== null && $longitude !== null) {
                $ad->setLatitude($latitude);
                $ad->setLongitude($longitude);
            }

            $ad->addSide($sideCode);
            $side = $ad->getSideByCode($sideCode);
            if (!$side instanceof AdvertisementSide) {
                $side = (new AdvertisementSide())->setCode($sideCode);
                $ad->addSideItem($side);
            }

            $side->setPrice($this->parsePrice($this->readCell($sheet, $row, $headers, 'основнойпрайс')));

            $imageLink = $this->readLink($sheet, $row, $headers, 'фото');
            if ($imageLink !== null) {
                $side->setImage($imageLink);
            }

            $mapLink = $this->readLink($sheet, $row, $headers, 'ссылканакартурекламного');
            if ($mapLink !== null) {
                $side->setDescription(sprintf('Ссылка на карту: %s', $mapLink));
            }

            $this->em->persist($ad);
            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->em->flush();

        $io->success(sprintf('Импорт завершен. Создано: %d, обновлено: %d, пропущено: %d.', $created, $updated, $skipped));
        $io->note('Колонки по продажам (например, "Продано") игнорируются и не участвуют в отборе.');

        return Command::SUCCESS;
    }

    private function readCell($sheet, int $row, array $headers, string $header): string
    {
        $column = $headers[$header] ?? null;
        if ($column === null) {
            return '';
        }

        return trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row)->getFormattedValue());
    }

    private function readLink($sheet, int $row, array $headers, string $header): ?string
    {
        $column = $headers[$header] ?? null;
        if ($column === null) {
            return null;
        }

        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);
        $url = trim((string) $cell->getHyperlink()->getUrl());
        if ($url !== '') {
            return $url;
        }

        $value = trim((string) $cell->getValue());
        return $value !== '' ? $value : null;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower($header);
        $header = preg_replace('/\s+/u', '', $header) ?? $header;
        return str_replace(['\n', '\r', 'ё'], ['', '', 'е'], $header);
    }

    private function emptyToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function parseCoordinates(string $value): array
    {
        if ($value === '') {
            return [null, null];
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) < 2) {
            return [null, null];
        }

        $lat = $this->toFloat($parts[0]);
        $lon = $this->toFloat($parts[1]);

        return [$lat, $lon];
    }

    private function toFloat(string $value): ?float
    {
        $normalized = str_replace([' ', ','], ['', '.'], $value);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function parsePrice(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d,\.]/u', '', $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && !str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? number_format((float) $normalized, 2, '.', '') : null;
    }
}

