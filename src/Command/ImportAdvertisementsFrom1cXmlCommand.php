<?php

namespace App\Command;

use App\Entity\Advertisement;
use App\Entity\AdvertisementCategory;
use App\Entity\AdvertisementSide;
use App\Entity\AdvertisementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:ads-from-1c-xml',
    description: 'Импортирует рекламные конструкции из XML-выгрузки 1С в advertisement/advertisement_side'
)]
class ImportAdvertisementsFrom1cXmlCommand extends Command
{
    /**
     * @var array<string, AdvertisementCategory>
     */
    private array $categoryCache = [];

    /**
     * @var array<string, AdvertisementType>
     */
    private array $typeCacheByName = [];

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Путь к XML-файлу 1С', 'MessageFor_ST0000000007.xml')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Количество конструкций до промежуточного flush()', '100')
            ->setHelp(<<<'HELP'
Команда читает XML-выгрузку 1С потоковым XMLReader, разбирает справочники
CatalogObject.Размеры, CatalogObject.ВидыСторон, CatalogObject.ТипыРекламныхБлоков,
а затем группирует CatalogObject.РекламныеБлоки по номеру конструкции.

Сопоставление полей 1С:
  - ТипБлока -> AdvertisementType, данные типа и размера сохраняются в sourceData типа;
  - Сторона -> AdvertisementSide.code через справочник ВидыСторон;
  - НомерБлока/Номер -> Advertisement.placeNumber/code;
  - Адрес/Adress -> Advertisement.address;
  - Координаты -> AdvertisementLocation latitude/longitude;
  - Описание/Specification и СсылкаНаКартуРекламногоМеста -> AdvertisementSide.description;
  - Фото/ОсновноеИзображение -> AdvertisementSide.image;
  - Ref/исходная строка 1С -> sourceRef/sourceData для Advertisement и AdvertisementSide.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('file');
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        if (!is_file($path)) {
            $io->error(sprintf('XML-файл не найден: %s', $path));
            return Command::FAILURE;
        }

        [$sizes, $sideKinds, $blockTypes, $blockRows, $stats] = $this->readXml($path);
        if ($blockRows === []) {
            $io->warning('В XML не найдено активных рекламных блоков для импорта.');
            return Command::SUCCESS;
        }

        $this->warmCaches();
        $this->syncTypes($blockTypes, $sizes);
        $this->em->flush();

        $groups = $this->groupRows($blockRows, $sideKinds, $blockTypes);
        $adRepo = $this->em->getRepository(Advertisement::class);
        $adCache = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $processed = 0;

        foreach ($groups as $group) {
            $placeNumber = $group['place_number'];
            $typeName = $group['type_name'];
            if ($placeNumber === null || $typeName === null) {
                $skipped++;
                continue;
            }

            $type = $this->resolveType($typeName);
            if (!$type instanceof AdvertisementType) {
                $io->warning(sprintf('Тип "%s" не найден и не создан. Конструкция "%s" пропущена.', $typeName, $placeNumber));
                $skipped++;
                continue;
            }

            $adCacheKey = $group['import_key'];
            $ad = $adCache[$adCacheKey] ?? null;
            if (!$ad instanceof Advertisement) {
                $criteria = ['placeNumber' => $placeNumber];
                if ($group['address'] !== null) {
                    $criteria['address'] = $group['address'];
                }
                $ad = $adRepo->findOneBy($criteria);
                $adCache[$adCacheKey] = $ad;
            }

            $isNew = false;
            if (!$ad instanceof Advertisement) {
                $ad = new Advertisement();
                $ad->setPlaceNumber($placeNumber);
                $ad->setCode($placeNumber);
                $adCache[$adCacheKey] = $ad;
                $isNew = true;
            }

            $ad->setType($type);
            $ad->setSourceRef($group['source_ref']);
            $ad->setSourceData($group['source_data']);

            if ($group['address'] !== null) {
                $ad->setAddress($group['address']);
            }

            if ($group['latitude'] !== null && $group['longitude'] !== null) {
                $ad->setLatitude($group['latitude']);
                $ad->setLongitude($group['longitude']);
            }

            foreach ($group['sides'] as $sideCode => $sideData) {
                $ad->addSide($sideCode);
                $side = $ad->getSideByCode($sideCode);
                if (!$side instanceof AdvertisementSide) {
                    $side = (new AdvertisementSide())->setCode($sideCode);
                    $ad->addSideItem($side);
                }

                $side->setSourceRef($sideData['source_ref']);
                $side->setSourceData($sideData['source_data']);

                if ($sideData['description'] !== null) {
                    $side->setDescription($sideData['description']);
                }

                if ($sideData['image'] !== null) {
                    $side->setImage($sideData['image']);
                }
            }

            $this->em->persist($ad);
            $isNew ? $created++ : $updated++;
            $processed++;

            if ($processed % $batchSize === 0) {
                $this->em->flush();
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            'Импорт XML 1С завершен. Справочники: размеры %d, виды сторон %d, типы блоков %d. Блоков прочитано: %d, групп конструкций: %d. Создано: %d, обновлено: %d, пропущено: %d.',
            $stats['sizes'],
            $stats['side_kinds'],
            $stats['block_types'],
            $stats['block_rows'],
            count($groups),
            $created,
            $updated,
            $skipped
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, string>, 2: array<string, array<string, mixed>>, 3: array<int, array<string, mixed>>, 4: array<string, int>}
     */
    private function readXml(string $path): array
    {
        $reader = new \XMLReader();
        if (!$reader->open($path, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException(sprintf('Не удалось открыть XML-файл: %s', $path));
        }

        $sizes = [];
        $sideKinds = [];
        $blockTypes = [];
        $blockRows = [];
        $stats = ['sizes' => 0, 'side_kinds' => 0, 'block_types' => 0, 'block_rows' => 0];

        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }

            $nodeName = $reader->name;
            if (!in_array($nodeName, ['CatalogObject.Размеры', 'CatalogObject.ВидыСторон', 'CatalogObject.ТипыРекламныхБлоков', 'CatalogObject.РекламныеБлоки'], true)) {
                continue;
            }

            $row = $this->readObject($reader);
            $ref = $this->first($row, 'Ref');
            if ($ref === null) {
                continue;
            }

            if ($this->isDeleted($row)) {
                continue;
            }

            if ($nodeName === 'CatalogObject.Размеры') {
                $sizes[$ref] = $row;
                $stats['sizes']++;
            } elseif ($nodeName === 'CatalogObject.ВидыСторон') {
                $sideName = $this->first($row, 'Description') ?? $this->first($row, 'PredefinedDataName');
                if ($sideName !== null) {
                    $sideKinds[$ref] = mb_strtoupper($sideName);
                }
                $stats['side_kinds']++;
            } elseif ($nodeName === 'CatalogObject.ТипыРекламныхБлоков') {
                $blockTypes[$ref] = $row;
                $stats['block_types']++;
            } else {
                $blockRows[] = $row;
                $stats['block_rows']++;
            }
        }

        $reader->close();

        return [$sizes, $sideKinds, $blockTypes, $blockRows, $stats];
    }

    /**
     * @return array<string, mixed>
     */
    private function readObject(\XMLReader $reader): array
    {
        $xml = $reader->readOuterXml();
        $element = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        if (!$element instanceof \SimpleXMLElement) {
            return [];
        }

        $row = [];
        foreach ($element->children() as $child) {
            $name = $child->getName();
            $value = trim((string) $child);
            if ($value === '') {
                continue;
            }

            if (!isset($row[$name])) {
                $row[$name] = $value;
                continue;
            }

            if (!is_array($row[$name])) {
                $row[$name] = [$row[$name]];
            }
            $row[$name][] = $value;
        }

        return $row;
    }

    /**
     * @param array<string, array<string, mixed>> $blockTypes
     * @param array<string, array<string, mixed>> $sizes
     */
    private function syncTypes(array $blockTypes, array $sizes): void
    {
        foreach ($blockTypes as $ref => $row) {
            $name = $this->first($row, 'Description');
            if ($name === null) {
                continue;
            }

            $type = $this->resolveType($name) ?? new AdvertisementType();
            $type->setName($name);
            $type->setCategory($this->resolveCategory($this->categoryNameFor($name, $this->first($row, 'ТипКонструкции'))));
            $type->setSourceRef($ref);
            $type->setSourceData([
                'catalog' => 'CatalogObject.ТипыРекламныхБлоков',
                'raw' => $row,
                'size' => $sizes[$this->first($row, 'Размер') ?? ''] ?? null,
            ]);

            $this->em->persist($type);
            $this->typeCacheByName[$this->normalize($name)] = $type;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, string> $sideKinds
     * @param array<string, array<string, mixed>> $blockTypes
     * @return array<int, array<string, mixed>>
     */
    private function groupRows(array $rows, array $sideKinds, array $blockTypes): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $placeNumber = $this->first($row, 'НомерБлока') ?? $this->first($row, 'Номер') ?? $this->placeNumberFromDescription($this->first($row, 'Description'));
            $typeRef = $this->first($row, 'ТипБлока');
            $typeName = $typeRef !== null ? $this->first($blockTypes[$typeRef] ?? [], 'Description') : null;
            $typeName ??= $this->first($row, 'ТипМеста');
            $sideRef = $this->last($row, 'Сторона');
            $sideCode = $sideRef !== null ? ($sideKinds[$sideRef] ?? null) : null;
            $sideCode ??= $this->sideFromDescription($this->first($row, 'Description'));
            $sideCode = $sideCode !== null ? mb_strtoupper($sideCode) : null;

            if ($placeNumber === null || $sideCode === null) {
                continue;
            }

            $address = $this->first($row, 'Адрес') ?? $this->first($row, 'Adress');
            $coordinateValue = $this->last($row, 'Координаты');
            $groupKey = implode('|', [
                $placeNumber,
                $this->normalize($address ?? ''),
                $typeRef ?? '',
                $coordinateValue ?? '',
            ]);
            if (!isset($groups[$groupKey])) {
                $coordinates = $this->parseCoordinates($coordinateValue);
                $groups[$groupKey] = [
                    'import_key' => $groupKey,
                    'place_number' => $placeNumber,
                    'type_name' => $typeName,
                    'source_ref' => $this->first($row, 'Ref'),
                    'address' => $address,
                    'latitude' => $coordinates[0],
                    'longitude' => $coordinates[1],
                    'sides' => [],
                    'source_data' => [
                        'catalog' => 'CatalogObject.РекламныеБлоки',
                        'grouped_by' => 'НомерБлока/Номер + Адрес + ТипБлока + Координаты',
                        'type_ref' => $typeRef,
                        'rows' => [],
                    ],
                ];
            }

            if ($groups[$groupKey]['type_name'] === null && $typeName !== null) {
                $groups[$groupKey]['type_name'] = $typeName;
            }
            if ($groups[$groupKey]['address'] === null) {
                $groups[$groupKey]['address'] = $this->first($row, 'Адрес') ?? $this->first($row, 'Adress');
            }
            if ($groups[$groupKey]['latitude'] === null || $groups[$groupKey]['longitude'] === null) {
                $coordinates = $this->parseCoordinates($this->last($row, 'Координаты'));
                $groups[$groupKey]['latitude'] = $coordinates[0];
                $groups[$groupKey]['longitude'] = $coordinates[1];
            }

            $description = $this->buildSideDescription(
                $this->last($row, 'Описание') ?? $this->first($row, 'Specification'),
                $this->first($row, 'СсылкаНаКартуРекламногоМеста')
            );
            $groups[$groupKey]['sides'][$sideCode] = [
                'source_ref' => $this->first($row, 'Ref'),
                'description' => $description,
                'image' => $this->first($row, 'Фото') ?? $this->first($row, 'ОсновноеИзображение'),
                'source_data' => [
                    'catalog' => 'CatalogObject.РекламныеБлоки',
                    'side_ref' => $sideRef,
                    'raw' => $row,
                ],
            ];
            $groups[$groupKey]['source_data']['rows'][] = $row;
        }

        return array_values($groups);
    }

    private function warmCaches(): void
    {
        foreach ($this->em->getRepository(AdvertisementCategory::class)->findAll() as $category) {
            if ($category instanceof AdvertisementCategory) {
                $this->categoryCache[$this->normalize($category->getName())] = $category;
            }
        }

        foreach ($this->em->getRepository(AdvertisementType::class)->findAll() as $type) {
            if ($type instanceof AdvertisementType) {
                $this->typeCacheByName[$this->normalize($type->getName())] = $type;
            }
        }
    }

    private function resolveType(string $name): ?AdvertisementType
    {
        return $this->typeCacheByName[$this->normalize($name)] ?? null;
    }

    private function resolveCategory(string $name): AdvertisementCategory
    {
        $key = $this->normalize($name);
        $category = $this->categoryCache[$key] ?? null;
        if ($category instanceof AdvertisementCategory) {
            return $category;
        }

        $category = new AdvertisementCategory();
        $category->setName($name);
        $this->em->persist($category);
        $this->categoryCache[$key] = $category;

        return $category;
    }

    private function categoryNameFor(string $typeName, ?string $constructionKind): string
    {
        $haystack = mb_strtolower($typeName . ' ' . ($constructionKind ?? ''));
        return match (true) {
            str_contains($haystack, 'щит') => 'Щиты',
            str_contains($haystack, 'призматрон') => 'Призматроны',
            str_contains($haystack, 'экран'), str_contains($haystack, 'led'), str_contains($haystack, 'видео') => 'Видеоэкраны',
            str_contains($haystack, 'брандмауэр') => 'Брандмауэры',
            default => 'Прочие',
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function first(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function last(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if (is_array($value)) {
            $value = end($value);
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isDeleted(array $row): bool
    {
        return mb_strtolower($this->first($row, 'DeletionMark') ?? '') === 'true';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace('ё', 'е', $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function placeNumberFromDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $parts = array_map('trim', explode(',', $description));
        return $parts[0] ?? null;
    }

    private function sideFromDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $parts = array_map('trim', explode(',', $description));
        return $parts[1] ?? null;
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function parseCoordinates(?string $value): array
    {
        if ($value === null) {
            return [null, null];
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) < 2) {
            return [null, null];
        }

        $latitude = $this->toFloat($parts[0]);
        $longitude = $this->toFloat($parts[1]);

        return [$latitude, $longitude];
    }

    private function toFloat(string $value): ?float
    {
        $normalized = str_replace([' ', ','], ['', '.'], $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function buildSideDescription(?string $description, ?string $mapLink): ?string
    {
        $parts = [];
        if ($description !== null) {
            $parts[] = $description;
        }
        if ($mapLink !== null) {
            $parts[] = sprintf('Ссылка на карту: %s', $mapLink);
        }

        return $parts === [] ? null : implode("\n", $parts);
    }
}
