<?php


namespace App\Repository;

use App\Entity\AdvertisementType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvertisementType>
 */
class AdvertisementTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvertisementType::class);
    }

    public function findOrCreate(string $name): AdvertisementType
    {
        $type = $this->findOneBy(['name' => $name]);
        if (!$type) {
            $type = new AdvertisementType();
            $type->setName($name);
            $this->_em->persist($type);
            $this->_em->flush();
        }
        return $type;
    }

    public function findFilter( ?int $categoryId): array
    {
        return $this->createQueryBuilder('at')
            ->select('at.id,at.name')
            ->innerJoin('at.category', 'c')
            ->where('at.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('at.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
