<?php

namespace App\Repository;

use App\Entity\Advertisement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advertisement>
 */
class AdvertisementRepository extends ServiceEntityRepository
{
    private string $query = '
                a.id as id,
                ac.id AS category_id,
                ac.name AS category,
                at.id AS type_id,
                at.name AS type,
                a.code AS code,
                a.address AS address,
                a.sides AS side,
                a.placeNumber AS place_number,
                al.latitude AS latitude,
                al.longitude AS longitude';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advertisement::class);
    }

    public function findAll(): array
    {
        $query = $this->createQueryBuilder('a')
            ->select($this->query)
            ->innerJoin('a.type', 'at')
            ->innerJoin('at.category', 'ac')
            ->innerJoin('a.location', 'al')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
        return $query;

    }

    /**
     * Найти по уникальному коду
     */
    public function findOneByCode(int $code): ?Advertisement
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Найти все активные (например, статус "Установлена")
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'Установлена')
            ->getQuery()
            ->getResult();
    }


    public function findByFilters(?int $productType, ?int $constrType): array
    {
        if (!empty ($constrType) || !empty($productType)) {
            $query = $this->createQueryBuilder('a')
                ->select($this->query)
                ->innerJoin('a.type', 'at')
                ->innerJoin('at.category', 'ac')
                ->innerJoin('a.location', 'al');

            if (!empty($productType)) {
                $query->andWhere('ac.id = :category')
                    ->setParameter('category', $productType);
            }

            if (!empty($constrType)) {
                $query->andWhere('at.id = :type')
                    ->setParameter('type', $constrType);
            }

            return $query->orderBy('at.name', 'ASC')->getQuery()->getArrayResult();
        }

        return [];
    }


    /**
     * @return Advertisement[]
     */
    public function findForApi(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.type', 'at')->addSelect('at')
            ->leftJoin('at.category', 'ac')->addSelect('ac')
            ->leftJoin('a.location', 'al')->addSelect('al')
            ->leftJoin('a.sideItems', 'asi')->addSelect('asi')
            ->leftJoin('a.bookings', 'ab')->addSelect('ab')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Advertisement[]
     */
    public function findByFiltersForApi(?int $productType, ?int $constrType): array
    {
        $query = $this->createQueryBuilder('a')
            ->leftJoin('a.type', 'at')->addSelect('at')
            ->leftJoin('at.category', 'ac')->addSelect('ac')
            ->leftJoin('a.location', 'al')->addSelect('al')
            ->leftJoin('a.sideItems', 'asi')->addSelect('asi')
            ->leftJoin('a.bookings', 'ab')->addSelect('ab');

        if (!empty($productType)) {
            $query->andWhere('ac.id = :category')
                ->setParameter('category', $productType);
        }

        if (!empty($constrType)) {
            $query->andWhere('at.id = :type')
                ->setParameter('type', $constrType);
        }

        return $query->orderBy('a.id', 'ASC')->getQuery()->getResult();
    }

}
