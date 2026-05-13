<?php

namespace App\Repository;

use App\Entity\ProductRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductRequest>
 */
class ProductRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRequest::class);
    }

    /**
     * @return ProductRequest[]
     */
    public function findLatestByContactPhone(string $contactPhone, int $limit = 50): array
    {
        return $this->createQueryBuilder('pr')
            ->andWhere('pr.contactPhone = :contactPhone')
            ->setParameter('contactPhone', $contactPhone)
            ->orderBy('pr.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
