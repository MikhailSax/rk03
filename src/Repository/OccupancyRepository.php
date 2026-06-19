<?php

namespace App\Repository;

use App\Entity\AdvertisementSide;
use App\Entity\Occupancy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Occupancy>
 */
class OccupancyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Occupancy::class);
    }

    /**
     * Возвращает статус стороны на конкретный месяц (или null если нет данных из 1С).
     */
    public function findStatusForMonth(AdvertisementSide $side, \DateTimeImmutable $month): ?Occupancy
    {
        $firstDay = new \DateTimeImmutable($month->format('Y-m-01'));

        return $this->createQueryBuilder('o')
            ->andWhere('o.advertisementSide = :side')
            ->andWhere('o.month = :month')
            ->setParameter('side', $side)
            ->setParameter('month', $firstDay)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Все записи занятости для стороны, отсортированные по месяцу.
     *
     * @return Occupancy[]
     */
    public function findBySide(AdvertisementSide $side): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.advertisementSide = :side')
            ->setParameter('side', $side)
            ->orderBy('o.month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Занятые/забронированные стороны в диапазоне месяцев (для фронта карты).
     *
     * @return Occupancy[]
     */
    public function findBusyInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.month >= :from')
            ->andWhere('o.month <= :to')
            ->andWhere('o.status != :free')
            ->setParameter('from', new \DateTimeImmutable($from->format('Y-m-01')))
            ->setParameter('to', new \DateTimeImmutable($to->format('Y-m-01')))
            ->setParameter('free', Occupancy::STATUS_FREE)
            ->getQuery()
            ->getResult();
    }
}
