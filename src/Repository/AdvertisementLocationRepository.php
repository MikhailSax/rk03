<?php

namespace App\Repository;

use App\Entity\AdvertisementLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvertisementLocation>
 */
class AdvertisementLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvertisementLocation::class);
    }

    /**
     * Найти все точки по городу
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.advertisement', 'a')
            ->andWhere('a.city = :city')
            ->setParameter('city', $city)
            ->getQuery()
            ->getResult();
    }
}
