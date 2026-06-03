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
            ->addOption('images-dir', null, InputOption::VALUE_REQUIRED, 'Папка с файлами изображений из 1С; найденные файлы будут скопированы в uploads')
            ->addOption('image-upload-dir', null, InputOption::VALUE_REQUIRED, 'Папка сайта для сохранения изображений', 'public/uploads/advertisements')
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
  - ОсновноеИзображение/Фото -> AdvertisementSide.image; если передан --images-dir, файлы копируются в public/uploads/advertisements;
  - Ref/исходная строка 1С -> sourceRef/sourceData для Advertisement и AdvertisementSide.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('file');
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $imagesDir = $this->stringOrNull($input->getOption('images-dir'));
        $imageUploadDir = $this->absolutePath((string) $input->getOption('image-upload-dir'));

        if (!is_file($path)) {
            $io->error(sprintf('XML-файл не найден: %s', $path));
            return Command::FAILURE;
        }

        if ($imagesDir !== null) {
            $imagesDir = $this->absolutePath($imagesDir);
            if (!is_dir($imagesDir)) {
                $io->error(sprintf('Папка изображений не найдена: %s', $imagesDir));
                return Command::FAILURE;
            }
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
        $copiedImages = 0;
        $missingImages = 0;

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

                $image = $this->prepareSideImage($sideData, $imagesDir, $imageUploadDir, $copiedImages, $missingImages);
                if ($image !== null) {
                    $side->setImage($image);
                    $this->syncLegacySideImage($ad, $sideCode, $image);
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
            'Импорт XML 1С завершен. Справочники: размеры %d, виды сторон %d, типы блоков %d. Блоков прочитано: %d, групп конструкций: %d. Создано: %d, обновлено: %d, пропущено: %d. Изображений скопировано: %d, не найдено: %d.',
            $stats['sizes'],
            $stats['side_kinds'],
            $stats['block_types'],
            $stats['block_rows'],
            count($groups),
            $created,
            $updated,
            $skipped,
            $copiedImages,
            $missingImages
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
            $imageCandidates = $this->imageCandidates($row);
            $groups[$groupKey]['sides'][$sideCode] = [
                'source_ref' => $this->first($row, 'Ref'),
                'description' => $description,
                'image' => $this->imageValue($imageCandidates),
                'image_candidates' => $imageCandidates,
                'source_data' => [
                    'catalog' => 'CatalogObject.РекламныеБлоки',
                    'side_ref' => $sideRef,
                    'image_candidates' => $imageCandidates,
                    'raw' => $row,
                ],
            ];
            $groups[$groupKey]['source_data']['rows'][] = $row;
        }

        return array_values($groups);
    }


    /**
     * @param array<string, mixed> $sideData
     */
    private function prepareSideImage(array $sideData, ?string $imagesDir, string $imageUploadDir, int &$copiedImages, int &$missingImages): ?string
    {
        $image = $this->stringOrNull($sideData['image'] ?? null);
        if ($image !== null && $this->isUrl($image)) {
            return $image;
        }

        $candidates = $sideData['image_candidates'] ?? [];
        if (!is_array($candidates)) {
            $candidates = [];
        }

        if ($imagesDir !== null) {
            $sourcePath = $this->findImageSource($imagesDir, $candidates);
            if ($sourcePath !== null) {
                if (!is_dir($imageUploadDir) && !mkdir($imageUploadDir, 0775, true) && !is_dir($imageUploadDir)) {
                    throw new \RuntimeException(sprintf('Не удалось создать папку для изображений: %s', $imageUploadDir));
                }

                $targetName = $this->targetImageName($sourcePath, $sideData['source_ref'] ?? null);
                $targetPath = rtrim($imageUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $targetName;
                if (!is_file($targetPath)) {
                    if (!copy($sourcePath, $targetPath)) {
                        throw new \RuntimeException(sprintf('Не удалось скопировать изображение 1С из %s в %s', $sourcePath, $targetPath));
                    }
                    $copiedImages++;
                }

                return $targetName;
            }

            if ($candidates !== []) {
                $missingImages++;
            }
        }

        if ($image !== null && $this->isImageFileName($image)) {
            return basename($image);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @return string[]
     */
    private function imageCandidates(array $row): array
    {
        $candidates = [];
        foreach (['ОсновноеИзображение', 'Фото'] as $field) {
            $value = $this->first($row, $field);
            if ($value === null || $this->isZeroUuid($value)) {
                continue;
            }

            $candidates[] = $value;
            if ($this->isUuid($value)) {
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
                    $candidates[] = $value . '.' . $extension;
                    $candidates[] = $value . '_pic.' . $extension;
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param string[] $candidates
     */
    private function imageValue(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($this->isUrl($candidate) || $this->isImageFileName($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param string[] $candidates
     */
    private function findImageSource(string $imagesDir, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($this->isUrl($candidate)) {
                continue;
            }

            $safeCandidate = basename($candidate);
            if ($safeCandidate === '') {
                continue;
            }

            $path = rtrim($imagesDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeCandidate;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function targetImageName(string $sourcePath, mixed $sourceRef): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $base = $this->stringOrNull($sourceRef) ?? pathinfo($sourcePath, PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $base) ?: sha1($sourcePath);

        return sprintf('1c-%s.%s', trim($base, '-'), strtolower($extension));
    }

    private function syncLegacySideImage(Advertisement $ad, string $sideCode, string $image): void
    {
        if ($sideCode === 'A') {
            $ad->setSideAImage($image);
        } elseif ($sideCode === 'B') {
            $ad->setSideBImage($image);
        }
    }

    private function absolutePath(string $path): string
    {
        if ($path === '') {
            return getcwd() ?: '.';
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[\\/]#', $path) === 1) {
            return $path;
        }

        return rtrim(getcwd() ?: '.', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isUrl(string $value): bool
    {
        return preg_match('#^https?://#i', $value) === 1;
    }

    private function isImageFileName(string $value): bool
    {
        return preg_match('/\.(?:jpe?g|png|webp|gif)$/i', parse_url($value, PHP_URL_PATH) ?: $value) === 1;
    }

    private function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    private function isZeroUuid(string $value): bool
    {
        return $value === '00000000-0000-0000-0000-000000000000';
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
        return $parts === [] ? null : implode("\n", $parts);
    }
}
