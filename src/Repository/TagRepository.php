<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * This custom Doctrine repository is empty because so far we don't need any custom
 * method to query for application user information. But it's always a good practice
 * to define a custom repository that will be used when the application grows.
 *
 * See https://symfony.com/doc/current/doctrine/repository.html
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
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
            foreach (range('a', 'z') as $letter) {
                $queryBuilder->andWhere("p.name NOT LIKE '" . $letter . "%'");
            }
        } else {
            $queryBuilder->where('p.name LIKE :word')->setParameter('word', $letter . '%');
        }
        $queryBuilder->leftJoin('p.mangas', 'mangas')
            ->addSelect('COUNT(mangas) as total')
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
