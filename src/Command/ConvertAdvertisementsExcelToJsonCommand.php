<?php

namespace App\Command;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:convert:ads-excel-to-json',
    description: 'Конвертирует Excel-файл с конструкциями в advertisements.json и advertisement_types.json'
)]
class ConvertAdvertisementsExcelToJsonCommand extends Command
{
    protected function configure(): void
    {
        $defaultDir = dirname(__DIR__) . '/DataFixtures/data';

        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Путь до Excel-файла (.xlsx/.xls).')
            ->addOption('ads-output', null, InputOption::VALUE_REQUIRED, 'Куда сохранить advertisements.json', $defaultDir . '/advertisements.json')
            ->addOption('types-output', null, InputOption::VALUE_REQUIRED, 'Куда сохранить advertisement_types.json', $defaultDir . '/advertisement_types.json')
            ->setHelp(<<<'HELP'
Шаг 1 (конвертация):
  php bin/console app:convert:ads-excel-to-json /path/to/list.xlsx

Шаг 2 (импорт в БД):
  php bin/console app:import:ads-from-json

Если путь к файлу не передан, команда попробует взять list.xlsx из корня проекта.
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

        $typeNameToId = [];
        $types = [];
        $adsMap = [];
        $skipped = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $placeNumber = $this->readCell($sheet, $row, $headers, 'номер');
            $sideCode = mb_strtoupper($this->readCell($sheet, $row, $headers, 'сторона'));
            $typeName = $this->readCell($sheet, $row, $headers, 'типконструкции');

            if ($placeNumber === '' || $sideCode === '' || $typeName === '') {
                continue;
            }

            if (!isset($typeNameToId[$typeName])) {
                $typeId = count($typeNameToId) + 1;
                $typeNameToId[$typeName] = $typeId;
                $types[] = [
                    'id' => $typeId,
                    'name' => $typeName,
                ];
            }

            if (!isset($adsMap[$placeNumber])) {
                [$latitude, $longitude] = $this->parseCoordinates($this->readCell($sheet, $row, $headers, 'координаты'));

                $adsMap[$placeNumber] = [
                    'place_number' => $placeNumber,
                    'address' => $this->emptyToNull($this->readCell($sheet, $row, $headers, 'адрес')),
                    'sides' => [],
                    'type_id' => $typeNameToId[$typeName],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ];
            }

            if ($adsMap[$placeNumber]['type_id'] !== $typeNameToId[$typeName]) {
                $io->warning(sprintf('Строка %d: для номера %s встретился другой тип конструкции. Оставлен первый тип.', $row, $placeNumber));
                $skipped++;
            }

            if (!in_array($sideCode, $adsMap[$placeNumber]['sides'], true)) {
                $adsMap[$placeNumber]['sides'][] = $sideCode;
            }

            if ($adsMap[$placeNumber]['address'] === null) {
                $adsMap[$placeNumber]['address'] = $this->emptyToNull($this->readCell($sheet, $row, $headers, 'адрес'));
            }
        }

        $ads = array_values($adsMap);

        $adsPath = (string) $input->getOption('ads-output');
        $typesPath = (string) $input->getOption('types-output');

        $this->writeJson($typesPath, $types);
        $this->writeJson($adsPath, $ads);

        $io->success(sprintf(
            'Готово: типов %d, конструкций %d. Файлы: %s и %s',
            count($types),
            count($ads),
            $typesPath,
            $adsPath
        ));

        if ($skipped > 0) {
            $io->note(sprintf('Обнаружено %d строк с конфликтом типа конструкции для одного номера.', $skipped));
        }

        return Command::SUCCESS;
    }

    private function writeJson(string $filePath, array $data): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $filePath,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    private function readCell($sheet, int $row, array $headers, string $header): string
    {
        $column = $headers[$header] ?? null;
        if ($column === null) {
            return '';
        }

        return trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row)->getFormattedValue());
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
}
