<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findByFirstLetter(string $letter)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        if (strtolower($letter) === 'other') {
            $queryBuilder->andWhere('NOT REGEXP(p.name, :regexp) = true')
                ->setParameter('regexp', '^[a-z]');
        } else {
            $queryBuilder->where('p.name LIKE :word')->setParameter('word', $letter . '%');
        }
        $queryBuilder->join('p.mangas', 'mangas')
            ->addSelect('COUNT(mangas) as total')
            ->having('total > 9')
            ->orderBy('p.name')
            ->groupBy('p');
        return $queryBuilder->getQuery()->getResult();
    }

    public function findWithMangaCounter()
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.mangas', 'mangas')
            ->addSelect('COUNT(mangas) as total')
            ->orderBy('p.name')
            ->groupBy('p');

        return $queryBuilder->getQuery()->getResult();
    }
}
