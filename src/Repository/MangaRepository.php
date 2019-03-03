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

use App\Entity\Manga;
use App\Entity\Tag;
use App\Entity\Author;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * This custom Doctrine repository contains some methods which are useful when
 * querying for blog manga information.
 *
 * See https://symfony.com/doc/current/doctrine/repository.html
 *
 */
class MangaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manga::class);
    }

    public function findLatest(int $page = 1, Tag $tag = null): Pagerfanta
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.publishedAt <= :now')
            ->orderBy('p.publishedAt', 'DESC')
            ->setParameter('now', new \DateTime());

        if (null !== $tag) {
            $qb->andWhere(':tag MEMBER OF p.tags')
                ->setParameter('tag', $tag);
        }

        return $this->createPaginator($qb->getQuery(), $page);
    }

    private function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Manga::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * @return Manga[]
     */
    public function findBySearchQuery(string $rawQuery, int $page = 1): Pagerfanta
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === \count($searchTerms)) {
            return [];
        }
       
        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->andWhere('p.title LIKE :t_'.$key)
                ->setParameter('t_'.$key, '%'.$term.'%')
            ;
        }
        $queryBuilder
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    /**
     * Removes all non-alphanumeric characters except whitespaces.
     */
    private function sanitizeSearchQuery(string $query): string
    {
        return trim(preg_replace('/[[:space:]]+/', ' ', $query));
    }

    /**
     * Splits the search query into terms and removes the ones which are irrelevant.
     */
    private function extractSearchTerms(string $searchQuery): array
    {
        $terms = array_unique(explode(' ', $searchQuery));

        return array_filter($terms, function ($term) {
            return 1 <= mb_strlen($term);
        });
    }
}
