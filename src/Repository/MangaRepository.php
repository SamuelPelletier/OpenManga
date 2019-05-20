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
use App\Entity\Language;
use App\Entity\Parody;
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

    public function findLatest(int $page = 1): Pagerfanta
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC');

        return $this->createPaginator($queryBuilder->getQuery(), $page);
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

        $em = $this->getEntityManager();
        $repoLanguage = $em->getRepository(Language::class);
        $repoTag = $em->getRepository(Tag::class);
        $repoAuthor = $em->getRepository(Author::class);
        $repoParody = $em->getRepository(Parody::class);

        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($searchTerms as $key => $term) {
            $tags = $repoTag->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from($repoTag->_entityName, 'a')->where('a.name like :name')
                ->setParameter('name', '%' . $term . '%')->getQuery()->getResult();

            foreach ($tags as $keyTag => $tag) {
                $queryBuilder
                    ->orWhere(':ta_' . $keyTag . '_' . $key . ' MEMBER OF p.tags')
                    ->setParameter('ta_' . $keyTag . '_' . $key, $tag);
            }

            $languages = $repoLanguage->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from($repoLanguage->_entityName, 'a')->where('a.name like :name')
                ->setParameter('name', '%' . $term . '%')->getQuery()->getResult();

            foreach ($languages as $keyLanguage => $language) {
                $queryBuilder
                    ->orWhere(':l_' . $keyLanguage . '_' . $key . ' MEMBER OF p.languages')
                    ->setParameter(':l_' . $keyLanguage . '_' . $key, $language);
            }

            $parodies = $repoParody->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from($repoParody->_entityName, 'a')->where('a.name like :name')
                ->setParameter('name', '%' . $term . '%')->getQuery()->getResult();

            foreach ($parodies as $keyParody => $parody) {
                $queryBuilder
                    ->orWhere(':p_' . $keyParody . '_' . $key . ' MEMBER OF p.parodies')
                    ->setParameter(':p_' . $keyParody . '_' . $key, $parody);
            }

            $authors = $repoAuthor->getEntityManager()->createQueryBuilder()
                ->select('a')
                ->from($repoAuthor->_entityName, 'a')->where('a.name like :name')
                ->setParameter('name', '%' . $term . '%')->getQuery()->getResult();

            foreach ($authors as $keyAuthor => $author) {
                $queryBuilder
                    ->orWhere(':a_' . $keyAuthor . '_' . $key . ' MEMBER OF p.authors')
                    ->setParameter(':a_' . $keyAuthor . '_' . $key, $author);
            }

            $queryBuilder
                ->orWhere('p.title LIKE :ti_' . $key)
                ->setParameter('ti_' . $key, '%' . $term . '%')
                ->orderBy('p.publishedAt', 'DESC');
        }

        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    public function countByTag(Tag $tag): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->orWhere(':tag MEMBER OF p.tags')
            ->setParameter('tag', $tag);
        return count($queryBuilder->getQuery()->execute());
    }

    public function countByLanguage(Language $language): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->orWhere(':language MEMBER OF p.languages')
            ->setParameter('language', $language);
        return count($queryBuilder->getQuery()->execute());
    }

    public function countByParody(Parody $parody): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->orWhere(':parody MEMBER OF p.parodies')
            ->setParameter('parody', $parody);
        return count($queryBuilder->getQuery()->execute());
    }

    public function countByAuthor(Author $author): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->orWhere(':author MEMBER OF p.authors')
            ->setParameter('author', $author);
        return count($queryBuilder->getQuery()->execute());
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