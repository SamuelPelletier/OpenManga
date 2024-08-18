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
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

    public function findLatest(int $page = 1, bool $isSortByViews = false): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.publishedAt < :five_minutes_ago')
            ->setParameter('five_minutes_ago', (new DateTime("5 minutes ago"))->format("Y-m-d H:i:s"))
            ->andWhere('p.isCorrupted = false');

        if ($isSortByViews) {
            $queryBuilder->orderBy('p.countViews', 'DESC');
        } else {
            $queryBuilder->orderBy('p.publishedAt', 'DESC');
        }

        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    public function findTrending(int $page = 1): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->addOrderBy('p.countViews', 'DESC');

        $now = new DateTimeImmutable();
        $thirtyDaysAgo = $now->sub(new \DateInterval("P30D"));
        $queryBuilder->where('p.publishedAt > :thirty_days_ago')
            ->setParameter('thirty_days_ago', $thirtyDaysAgo->format('Y-m-d'))
            ->andWhere('p.publishedAt < :five_minutes_ago')
            ->setParameter('five_minutes_ago', (new DateTime("5 minutes ago"))->format("Y-m-d H:i:s"))
            ->andWhere('p.isCorrupted = false');

        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    /**
     * @return Manga[]
     */
    public function findBySearchQuery(
        string $rawQuery,
        int    $page = 1,
        bool   $isSortByViews = false,
        bool   $isStrict = false
    ): Paginator
    {
        $query = $this->sanitizeSearchQuery($rawQuery);

        // Min 3 caracteres to search
        if (strlen($query) < 3) {
            return $this->findLatest();
        }

        $em = $this->getEntityManager();
        $repoLanguage = $em->getRepository(Language::class);
        $repoTag = $em->getRepository(Tag::class);
        $repoAuthor = $em->getRepository(Author::class);
        $repoParody = $em->getRepository(Parody::class);

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false')->andWhere('p.publishedAt < :five_minutes_ago')
            ->setParameter('five_minutes_ago', (new DateTime("5 minutes ago"))->format("Y-m-d H:i:s"));

        $orStatements = $queryBuilder->expr()->orX();

        // If it's strict we use the entire query
        if ($isStrict) {
            $searchTerms = [$query];
        } else {
            $searchTerms = $this->extractSearchTerms($query);
        }

        foreach ($searchTerms as $key => $term) {
            if (!$isStrict) {
                $term = '%' . $term . '%';
            }

            if ($isStrict) {
                $tags = $repoTag->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoTag->_entityName, 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($tags as $keyTag => $tag) {
                    $paramName = 'ta_' . $keyTag;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.tags'));
                    $queryBuilder->setParameter($paramName, $tag);
                }

                $languages = $repoLanguage->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoLanguage->_entityName, 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($languages as $keyLanguage => $language) {
                    $paramName = 'l_' . $keyLanguage;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.languages'));
                    $queryBuilder->setParameter($paramName, $language);
                }

                $parodies = $repoParody->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoParody->_entityName, 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($parodies as $keyParody => $parody) {
                    $paramName = 'p_' . $keyParody;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.parodies'));
                    $queryBuilder->setParameter($paramName, $parody);
                }

                $authors = $repoAuthor->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoAuthor->_entityName, 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($authors as $keyAuthor => $author) {
                    $paramName = 'a_' . $keyAuthor;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.parodies'));
                    $queryBuilder->setParameter($paramName, $author);
                }
            }

            $orStatements->add($queryBuilder->expr()->like('p.title', ':title'));
            $queryBuilder->setParameter(':title', $term);
        }
        if ($isSortByViews) {
            $queryBuilder->orderBy('p.countViews', 'DESC');
        } else {
            $queryBuilder->orderBy('p.publishedAt', 'DESC');
        }
        $queryBuilder->andWhere($orStatements);

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

    public function findByAuthor(Author $author): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false')
            ->andWhere(':author MEMBER OF p.authors')
            ->setParameter('author', $author)
            ->orderBy('p.id', 'DESC');

        return $queryBuilder->getQuery()->execute();
    }

    public function findByTag(Tag $tag, int $max = null, $order = 'DESC'): array
    {
        if (strtoupper($order) === 'ASC') {
            $order = 'ASC';
        } else {
            $order = 'DESC';
        }

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false')
            ->andWhere(':tag MEMBER OF p.tags')
            ->setParameter('tag', $tag)
            ->orderBy('p.id', $order)
            ->setMaxResults($max);

        return $queryBuilder->getQuery()->execute();

    }

    public function findDuplicate()
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p.title')
            ->where(
                $expr->in(
                    'p.title',
                    $this->createQueryBuilder('t')
                        ->select('t.title')
                        ->where('t.id != p.id')
                        ->getDQL()
                )
            )
            ->groupBy('p.title');
        return $queryBuilder->getQuery()->execute();
    }

    public function findLatestByIdDesc(bool $onlyOld = false)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.publishedAt < :five_minutes_ago')
            ->setParameter('five_minutes_ago', (new DateTime("5 minutes ago"))->format("Y-m-d H:i:s"))
            ->andWhere('p.isCorrupted = false')
            ->andWhere('p.isOld = ' . (int)$onlyOld)
            ->orderBy('p.id', 'DESC');
        return $queryBuilder->getQuery()->execute();
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

    private function createPaginator(Query $query, int $page): Paginator
    {

        $premierResultat = ($page - 1) * Manga::NUM_ITEMS;
        $query->setFirstResult($premierResultat)->setMaxResults(Manga::NUM_ITEMS);
        $paginator = new Paginator($query);

        return $paginator;
    }
}
