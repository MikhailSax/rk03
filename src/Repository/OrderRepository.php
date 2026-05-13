<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /** @return Order[] */
    public function findExpiredPendingOrders(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.reservedUntil <= :now')
            ->setParameter('status', Order::STATUS_PENDING)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findByUserOrdered(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'oi')->addSelect('oi')
            ->leftJoin('oi.advertisement', 'oa')->addSelect('oa')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
