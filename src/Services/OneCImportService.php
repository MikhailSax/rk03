<?php

namespace App\Services;

use App\Entity\AdvertisementSide;
use App\Entity\Occupancy;
use App\Repository\AdvertisementSideRepository;
use App\Repository\OccupancyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Импорт занятости из XML-выгрузки 1С.
 *
 * Реальная структура файла:
 *   <CatalogObject.ВидыСторон> — стороны (Ref = sourceRef в AdvertisementSide)
 *   <TableVoz>                 — отдельный глобальный блок со всеми Row
 *     <Row>
 *       <ID>uuid</ID>          — Ref стороны (AdvertisementSide.sourceRef)
 *       <Month>2026-07-01T00:00:00</Month>
 *       <Status>0|1|3</Status> — 0=свободно, 1=занято, 3=бронь
 *     </Row>
 *   </TableVoz>
 */
class OneCImportService
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly OccupancyRepository         $occupancyRepo,
        private readonly AdvertisementSideRepository $sideRepo,
        private readonly LoggerInterface             $logger,
    )
    {
    }

    /**
     * @return array{
     *   rows_read: int,
     *   occupancy_upserted: int,
     *   occupancy_skipped: int,
     *   sides_not_found: int,
     *   errors: string[]
     * }
     */
    public function importOccupancyFromFile(string $xmlPath): array
    {
        if (!is_file($xmlPath)) {
            throw new \RuntimeException(sprintf('XML-файл не найден: %s', $xmlPath));
        }

        $stats = [
            'rows_read' => 0,
            'occupancy_upserted' => 0,
            'occupancy_skipped' => 0,
            'sides_not_found' => 0,
            'errors' => [],
        ];

        $reader = new \XMLReader();
        if (!$reader->open($xmlPath, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException(sprintf('Не удалось открыть XML: %s', $xmlPath));
        }

        $now = new \DateTimeImmutable();
        $batchCount = 0;
        $batchSize = 200;
        $inTableVoz = false;

        while ($reader->read()) {
            // Входим в <TableVoz>
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->localName === 'TableVoz') {
                $inTableVoz = true;
                continue;
            }

            // Выходим из <TableVoz>
            if ($reader->nodeType === \XMLReader::END_ELEMENT && $reader->localName === 'TableVoz') {
                $inTableVoz = false;
                continue;
            }

            // Обрабатываем <Row> только внутри <TableVoz>
            if (!$inTableVoz
                || $reader->nodeType !== \XMLReader::ELEMENT
                || $reader->localName !== 'Row') {
                continue;
            }

            $xml = $reader->readOuterXml();
            $doc = new \DOMDocument('1.0', 'UTF-8');
            if (!@$doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR)) {
                $stats['occupancy_skipped']++;
                continue;
            }

            $root = $doc->documentElement;
            if ($root === null) {
                $stats['occupancy_skipped']++;
                continue;
            }

            $stats['rows_read']++;

            try {
                $this->processRow($root, $now, $stats, $batchCount, $batchSize);
            } catch (\Throwable $e) {
                $stats['errors'][] = $e->getMessage();
                $this->logger->error('Ошибка обработки Row', ['error' => $e->getMessage()]);
            }
        }

        $reader->close();

        if ($batchCount > 0) {
            $this->em->flush();
        }

        return $stats;
    }

    // ------------------------------------------------------------------ //

    private function processRow(
        \DOMElement        $row,
        \DateTimeImmutable $now,
        array              &$stats,
        int                &$batchCount,
        int                $batchSize,
    ): void
    {
        $sideRef = $this->text($row, 'ID');
        $monthStr = $this->text($row, 'Month');
        $statusRaw = $this->text($row, 'Status');

        if ($sideRef === null || $monthStr === null || $statusRaw === null) {
            $stats['occupancy_skipped']++;
            return;
        }

        $status = (int)$statusRaw;
        if (!in_array($status, [
            Occupancy::STATUS_FREE,
            Occupancy::STATUS_BUSY,
            Occupancy::STATUS_RESERVED,
        ], true)) {
            $stats['occupancy_skipped']++;
            return;
        }

        $monthDate = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $monthStr)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d', substr($monthStr, 0, 10));

        if ($monthDate === false) {
            $stats['occupancy_skipped']++;
            return;
        }

        $monthFirst = new \DateTimeImmutable($monthDate->format('Y-m-01'));

        // Ищем сторону по sourceRef
        $side = $this->sideRepo->findOneBy(['sourceRef' => $sideRef]);
        if ($side === null) {
            $stats['sides_not_found']++;
            return;
        }

        // Upsert
        $occ = $this->occupancyRepo->findOneBy([
            'advertisementSide' => $side,
            'month' => $monthFirst,
        ]);

        if ($occ === null) {
            $occ = new Occupancy();
            $occ->setAdvertisementSide($side);
            $occ->setMonth($monthFirst);
        }

        $occ->setStatus($status);
        $occ->setSourceId($sideRef);
        $occ->setUpdatedAt($now);

        $this->em->persist($occ);
        $stats['occupancy_upserted']++;

        $batchCount++;
        if ($batchCount % $batchSize === 0) {
            $this->em->flush();
            $this->em->clear(Occupancy::class);
        }
    }

    private function text(\DOMElement $el, string $tag): ?string
    {
        foreach ($el->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $tag) {
                $v = trim($child->textContent);
                return $v !== '' ? $v : null;
            }
        }
        return null;
    }
}
