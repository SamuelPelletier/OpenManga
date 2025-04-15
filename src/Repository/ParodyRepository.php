<?php

namespace App\Repository;

use App\Entity\Parody;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParodyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parody::class);
    }

    public function findByNameWithStart(string $search)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.name like :search')
            ->setParameter('search', $search . "%")
            ->setMaxResults(10)
            ->getQuery();

        return $queryBuilder->getResult();
    }
}
