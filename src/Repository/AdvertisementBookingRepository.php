<?php

namespace App\Repository;

use App\Entity\Advertisement;
use App\Entity\AdvertisementBooking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvertisementBooking>
 */
class AdvertisementBookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvertisementBooking::class);
    }

    public function hasOverlap(
        Advertisement $advertisement,
        string $sideCode,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?int $excludeBookingId = null,
    ): bool {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.advertisement = :advertisement')
            ->andWhere('b.sideCode = :sideCode')
            ->andWhere('b.startDate <= :endDate')
            ->andWhere('b.endDate >= :startDate')
            ->setParameter('advertisement', $advertisement)
            ->setParameter('sideCode', mb_strtoupper(trim($sideCode)))
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($excludeBookingId !== null) {
            $qb
                ->andWhere('b.id != :excludeBookingId')
                ->setParameter('excludeBookingId', $excludeBookingId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return AdvertisementBooking[]
     */
    public function findByAdvertisementAndMonth(
        Advertisement $advertisement,
        \DateTimeImmutable $monthStart,
        \DateTimeImmutable $monthEnd,
        ?string $sideCode = null,
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->andWhere('b.advertisement = :advertisement')
            ->andWhere('b.startDate <= :monthEnd')
            ->andWhere('b.endDate >= :monthStart')
            ->setParameter('advertisement', $advertisement)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->orderBy('b.startDate', 'ASC');

        if ($sideCode !== null && trim($sideCode) !== '') {
            $qb->andWhere('b.sideCode = :sideCode')
                ->setParameter('sideCode', mb_strtoupper(trim($sideCode)));
        }

        return $qb->getQuery()->getResult();
    }
}
