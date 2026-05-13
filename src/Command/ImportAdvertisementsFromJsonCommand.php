<?php

namespace App\Command;

use App\Entity\Advertisement;
use App\Entity\AdvertisementLocation;
use App\Entity\AdvertisementSide;
use App\Entity\AdvertisementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:ads-from-json',
    description: 'Импортирует конструкции из JSON (Worksheet/legacy) в advertisement/advertisement_side'
)]
class ImportAdvertisementsFromJsonCommand extends Command
{
    /**
     * @var array<string, AdvertisementType>
     */
    private array $typeMap = [];

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Путь до JSON-файла (формат Worksheet либо legacy advertisements.json).',
                dirname(__DIR__, 2) . '/src/DataFixtures/data/advertisements.json'
            )
            ->addOption(
                'types-file',
                null,
                InputOption::VALUE_REQUIRED,
                'Путь до advertisement_types.json для legacy-формата с type_id.',
                dirname(__DIR__, 2) . '/src/DataFixtures/data/advertisement_types.json'
            )
            ->setHelp(<<<'HELP'
Примеры:
  php bin/console app:import:ads-from-json
  php bin/console app:import:ads-from-json /home/ubuntu/.cursor/projects/workspace/uploads/output.json

Команда:
  - учитывает повторяющиеся номера конструкций и объединяет их по сторонам;
  - сохраняет фото стороны как ссылку (URL) в поле AdvertisementSide.image;
  - обновляет существующие записи, если номер конструкции уже есть в БД.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $adsPath = (string)$input->getArgument('file');
        $typesPath = (string)$input->getOption('types-file');

        if (!is_file($adsPath)) {
            $io->error(sprintf('JSON-файл не найден: %s', $adsPath));
            return Command::FAILURE;
        }

        $rawAds = json_decode((string)file_get_contents($adsPath), true);
        if (!is_array($rawAds)) {
            $io->error(sprintf('Не удалось распарсить JSON: %s', $adsPath));
            return Command::FAILURE;
        }

        $rows = $this->parseRows($rawAds, $typesPath, $io);
        if ($rows === []) {
            $io->warning('В файле не найдено валидных строк для импорта.');
            return Command::SUCCESS;
        }

        $this->warmTypeMap();
        $adRepo = $this->em->getRepository(Advertisement::class);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $adCache = [];

        foreach ($rows as $row) {
            $placeNumber = $this->stringOrNull($row['place_number'] ?? null);
            $typeName = $this->stringOrNull($row['type_name'] ?? null);
            $sideCodes = $this->parseSideCodes($this->stringOrNull($row['side'] ?? null) ?? '');

            if ($placeNumber === null || $typeName === null || $sideCodes === []) {
                $skipped++;
                continue;
            }

            $type = $this->resolveType($typeName);
            if (!$type instanceof AdvertisementType) {
                $io->warning(sprintf('Тип "%s" не найден в БД. Номер %s пропущен.', $typeName, $placeNumber));
                $skipped++;
                continue;
            }

            $ad = $adCache[$placeNumber] ?? null;
            if (!$ad instanceof Advertisement) {
                $ad = $adRepo->findOneBy(['placeNumber' => $placeNumber]);
                $adCache[$placeNumber] = $ad;
            }

            $isNew = false;
            if (!$ad instanceof Advertisement) {
                $ad = new Advertisement();
                $ad->setPlaceNumber($placeNumber);
                $ad->setCode($placeNumber);
                $adCache[$placeNumber] = $ad;
                $isNew = true;
            }

            $ad->setType($type);
            $address = $this->stringOrNull($row['address'] ?? null);
            if ($address !== null) {
                $ad->setAddress($address);
            }
            $description = $this->stringOrNull($row['description'] ?? null);
            $image = $this->stringOrNull($row['image'] ?? null);
            $price = $this->parsePrice($this->stringOrNull($row['price'] ?? null) ?? '');
            $mapLink = $this->stringOrNull($row['map_link'] ?? null);

            [$latitude, $longitude] = $this->parseCoordinates($this->stringOrNull($row['coordinates'] ?? null) ?? '');
            if ($latitude !== null && $longitude !== null) {
                $location = $ad->getLocation();
                if (!$location instanceof AdvertisementLocation) {
                    $location = (new AdvertisementLocation())->setAdvertisement($ad);
                    $ad->setLocation($location);
                }
                $location->setLatitude($latitude);
                $location->setLongitude($longitude);
                $this->em->persist($location);
            }

            foreach ($sideCodes as $sideCode) {
                $ad->addSide($sideCode);
                $side = $ad->getSideByCode($sideCode);
                if (!$side instanceof AdvertisementSide) {
                    $side = (new AdvertisementSide())->setCode($sideCode);
                    $ad->addSideItem($side);
                }

                if ($description !== null || $mapLink !== null) {
                    $side->setDescription($this->buildDescription($description, $mapLink));
                }

                if ($price !== null) {
                    $side->setPrice($price);
                }

                // В image храним ссылку на фото, если она передана.
                if ($image !== null) {
                    $side->setImage($image);
                }
            }

            $this->em->persist($ad);
            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->em->flush();
        $io->success(sprintf(
            'Импорт завершен. Создано: %d, обновлено: %d, пропущено: %d.',
            $created,
            $updated,
            $skipped
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseRows(array $rawAds, string $typesPath, SymfonyStyle $io): array
    {
        if (isset($rawAds['Worksheet']) && is_array($rawAds['Worksheet'])) {
            return $this->parseWorksheetRows($rawAds['Worksheet'], $io);
        }

        if (array_is_list($rawAds)) {
            return $this->parseLegacyRows($rawAds, $typesPath, $io);
        }

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $worksheetRows
     * @return array<int, array<string, mixed>>
     */
    private function parseWorksheetRows(array $worksheetRows, SymfonyStyle $io): array
    {
        $headerRowIndex = null;
        $headerMap = [];

        foreach ($worksheetRows as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }

            $candidateMap = [];
            foreach ($row as $columnKey => $value) {
                if (!is_string($columnKey) || !str_starts_with($columnKey, 'column_')) {
                    continue;
                }
                $normalized = $this->normalizeHeader((string)$value);
                if ($normalized !== '') {
                    $candidateMap[$normalized] = $columnKey;
                }
            }

            if (isset($candidateMap['номер'], $candidateMap['сторона'], $candidateMap['типконструкции'])) {
                $headerRowIndex = $idx;
                $headerMap = $candidateMap;
                break;
            }
        }

        if ($headerRowIndex === null) {
            $io->error('В Worksheet не найдена строка заголовка (Номер/Сторона/Тип конструкции).');
            return [];
        }

        $result = [];
        for ($i = $headerRowIndex + 1; $i < count($worksheetRows); $i++) {
            $row = $worksheetRows[$i];
            if (!is_array($row)) {
                continue;
            }

            $placeNumber = $this->readWorksheetCell($row, $headerMap, ['номер']);
            $side = $this->readWorksheetCell($row, $headerMap, ['сторона']);
            $typeName = $this->readWorksheetCell($row, $headerMap, ['типконструкции', 'тип']);

            if ($placeNumber === '' || $side === '' || $typeName === '') {
                continue;
            }

            $result[] = [
                'place_number' => $placeNumber,
                'side' => $side,
                'address' => $this->readWorksheetCell($row, $headerMap, ['адрес']),
                'type_name' => $typeName,
                'image' => $this->readWorksheetCell($row, $headerMap, ['фото']),
                'description' => $this->readWorksheetCell($row, $headerMap, ['описание']),
                'coordinates' => $this->readWorksheetCell($row, $headerMap, ['координаты']),
                'price' => $this->readWorksheetCell($row, $headerMap, ['основнойпрайс']),
                'map_link' => $this->readWorksheetCell($row, $headerMap, ['ссылканакартурекламного']),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $adsJson
     * @return array<int, array<string, mixed>>
     */
    private function parseLegacyRows(array $adsJson, string $typesPath, SymfonyStyle $io): array
    {
        if (!is_file($typesPath)) {
            $io->error(sprintf('Для legacy-формата не найден файл типов: %s', $typesPath));
            return [];
        }

        $typesJson = json_decode((string)file_get_contents($typesPath), true);
        if (!is_array($typesJson)) {
            $io->error(sprintf('Не удалось распарсить types JSON: %s', $typesPath));
            return [];
        }

        $typeIdToName = [];
        foreach ($typesJson as $t) {
            if (is_array($t) && isset($t['id'], $t['name'])) {
                $typeIdToName[(int)$t['id']] = (string)$t['name'];
            }
        }

        $rows = [];
        foreach ($adsJson as $row) {
            if (!is_array($row)) {
                continue;
            }
            $typeId = isset($row['type_id']) ? (int)$row['type_id'] : null;
            $typeName = $typeId !== null ? ($typeIdToName[$typeId] ?? null) : ($row['type_name'] ?? null);
            $sides = $row['sides'] ?? [];
            if (!is_array($sides)) {
                $sides = [$sides];
            }
            $sideCodes = [];
            foreach ($sides as $sideCodeRaw) {
                foreach ($this->parseSideCodes((string)$sideCodeRaw) as $parsedCode) {
                    $sideCodes[] = $parsedCode;
                }
            }
            $sideCodes = array_values(array_unique($sideCodes));

            $coordinates = isset($row['latitude'], $row['longitude'])
                ? sprintf('%s,%s', (string)$row['latitude'], (string)$row['longitude'])
                : null;

            // Новый legacy-вариант: side_details с полями по конкретной стороне.
            $sideDetails = $row['side_details'] ?? null;
            if (is_array($sideDetails) && $sideDetails !== []) {
                $detailsByCode = [];
                foreach ($sideDetails as $detail) {
                    if (!is_array($detail)) {
                        continue;
                    }
                    $code = $this->stringOrNull($detail['code'] ?? null);
                    if ($code === null) {
                        continue;
                    }
                    $normalizedCodes = $this->parseSideCodes($code);
                    if ($normalizedCodes === []) {
                        continue;
                    }
                    $detailsByCode[$normalizedCodes[0]] = $detail;
                }

                $allCodes = array_values(array_unique(array_merge($sideCodes, array_keys($detailsByCode))));

                foreach ($allCodes as $code) {
                    $detail = $detailsByCode[$code] ?? [];
                    $rows[] = [
                        'place_number' => $row['place_number'] ?? null,
                        'side' => $code,
                        'address' => $row['address'] ?? null,
                        'type_name' => $typeName,
                        'image' => $detail['image'] ?? ($row['image'] ?? null),
                        'description' => $detail['description'] ?? ($row['description'] ?? null),
                        'coordinates' => $coordinates,
                        'price' => $detail['price'] ?? ($row['price'] ?? null),
                        'map_link' => $detail['map_link'] ?? ($row['map_link'] ?? null),
                    ];
                }

                continue;
            }

            $rows[] = [
                'place_number' => $row['place_number'] ?? null,
                'side' => implode(',', array_map(static fn(mixed $s): string => (string)$s, $sides)),
                'address' => $row['address'] ?? null,
                'type_name' => $typeName,
                'image' => $row['image'] ?? null,
                'description' => $row['description'] ?? null,
                'coordinates' => $coordinates,
                'price' => $row['price'] ?? null,
                'map_link' => $row['map_link'] ?? null,
            ];
        }

        return $rows;
    }

    private function readWorksheetCell(array $row, array $headerMap, array $candidates): string
    {
        foreach ($candidates as $name) {
            $columnKey = $headerMap[$name] ?? null;
            if ($columnKey === null) {
                continue;
            }
            $value = $row[$columnKey] ?? null;
            if ($value === null) {
                return '';
            }

            return trim((string)$value);
        }

        return '';
    }

    private function normalizeHeader(?string $header): string
    {
        if ($header === null) {
            return '';
        }
        $normalized = mb_strtolower(trim($header));
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        return str_replace(['\n', '\r', 'ё'], ['', '', 'е'], $normalized);
    }

    /**
     * @return string[]
     */
    private function parseSideCodes(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $prepared = str_replace([';', '|', '/'], ',', $value);
        $parts = array_map('trim', explode(',', $prepared));

        $codes = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $upper = mb_strtoupper($part);
            $upper = strtr($upper, [
                'А' => 'A',
                'В' => 'B',
                'С' => 'C',
                'Е' => 'E',
                'Н' => 'H',
                'К' => 'K',
                'М' => 'M',
                'О' => 'O',
                'Р' => 'P',
                'Т' => 'T',
                'Х' => 'X',
            ]);
            $upper = preg_replace('/\s+/u', '', $upper) ?? $upper;
            if ($upper === '') {
                continue;
            }

            $codes[] = $upper;
        }

        return array_values(array_unique($codes));
    }

    private function warmTypeMap(): void
    {
        if ($this->typeMap !== []) {
            return;
        }

        $types = $this->em->getRepository(AdvertisementType::class)->findAll();
        foreach ($types as $type) {
            if (!$type instanceof AdvertisementType) {
                continue;
            }

            $this->typeMap[$this->normalizeTypeName($type->getName())] = $type;
        }
    }

    private function resolveType(string $typeName): ?AdvertisementType
    {
        $normalized = $this->normalizeTypeName($typeName);
        return $this->typeMap[$normalized] ?? null;
    }

    private function normalizeTypeName(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = strtr($normalized, [
            'х' => 'x',
            '×' => 'x',
            '*' => 'x',
        ]);
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}x]+/u', '', $normalized) ?? $normalized;

        return $normalized;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string)$value);
        return $str === '' ? null : $str;
    }

    private function buildDescription(?string $description, ?string $mapLink): ?string
    {
        if ($description === null && $mapLink === null) {
            return null;
        }

        if ($description === null) {
            return sprintf('Ссылка на карту: %s', $mapLink);
        }

        if ($mapLink === null) {
            return $description;
        }

        if (str_contains($description, $mapLink)) {
            return $description;
        }

        return sprintf("%s\nСсылка на карту: %s", rtrim($description), $mapLink);
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
        return is_numeric($normalized) ? (float)$normalized : null;
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

        return is_numeric($normalized) ? number_format((float)$normalized, 2, '.', '') : null;
    }
}
