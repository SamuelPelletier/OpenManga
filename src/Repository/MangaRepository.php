<?php

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

class MangaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manga::class);
    }

    public function findLatest(int $page = 1): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false')
            ->andWhere('p.isOld = false')
            ->orderBy('p.id', 'DESC')->getQuery();

        $queryBuilder->setResultCacheId('manga_latest_' . $page);
        $queryBuilder->setResultCacheLifeTime(300);

        return $this->createPaginator($queryBuilder, $page);
    }

    public function findTrending(int $page = 1): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->addOrderBy('p.countViews', 'DESC');

        $now = new DateTimeImmutable();
        $thirtyDaysAgo = $now->sub(new \DateInterval("P30D"));
        $query = $queryBuilder->where('p.publishedAt > :thirty_days_ago')
            ->setParameter('thirty_days_ago', $thirtyDaysAgo->format('Y-m-d'))
            ->andWhere('p.publishedAt < :five_minutes_ago')
            ->setParameter('five_minutes_ago', (new DateTime("5 minutes ago"))->format("Y-m-d H:i:s"))
            ->andWhere('p.isCorrupted = false')
            ->andWhere('p.isOld = false')
            ->getQuery();

        $query->setResultCacheId('manga_trending_' . $page);
        $query->setResultCacheLifeTime(300);

        return $this->createPaginator($query, $page);
    }

    /**
     * @return Manga[]
     */
    public function findBySearchQuery(
        string $rawQuery,
        int    $page = 1
    ): Paginator
    {
        $query = $this->sanitizeSearchQuery($rawQuery);

        // Min 3 caracteres to search
        if (strlen($query) <= 3) {
            return $this->findLatest();
        }

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false')
            ->andWhere('p.isOld = false');

        $orStatements = $queryBuilder->expr()->orX();

        $searchTerms = $this->extractSearchTerms($query);

        $i = 0;
        foreach ($searchTerms as $term) {
            if (strlen($term) <= 3) {
                continue;
            }
            $i++;
            $term = '%' . $term . '%';
            $orStatements->add($queryBuilder->expr()->like('p.title', ':title'));
            $queryBuilder->setParameter(':title', $term);
        }

        // Any term > 3
        if ($i === 0) {
            return $this->findLatest();
        }

        $queryBuilder->andWhere($orStatements)->orderBy('p.id', 'DESC');

        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    public function findByStrictTypeSearchQuery(
        string $rawQuery,
        int    $page = 1,
        string $type
    ): Paginator
    {
        $query = $this->sanitizeSearchQuery($rawQuery);

        // Min 3 caracteres to search
        if (strlen($query) <= 3) {
            return $this->findLatest();
        }

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false');

        $em = $this->getEntityManager();
        $orStatements = $queryBuilder->expr()->orX();
        switch ($type) {
            case 'language':
                $repoLanguage = $em->getRepository(Language::class);
                $languages = $repoLanguage->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoLanguage->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $query)->getQuery()->getResult();

                foreach ($languages as $keyLanguage => $language) {
                    $paramName = 'l_' . $keyLanguage;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.languages'));
                    $queryBuilder->setParameter($paramName, $language);
                }
                break;
            case 'tag':
                $repoTag = $em->getRepository(Tag::class);
                $tags = $repoTag->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoTag->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $query)->getQuery()->getResult();

                foreach ($tags as $keyTag => $tag) {
                    $paramName = 'ta_' . $keyTag;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.tags'));
                    $queryBuilder->setParameter($paramName, $tag);
                }
                break;
            case 'author':
                $repoAuthor = $em->getRepository(Author::class);
                $authors = $repoAuthor->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoAuthor->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $query)->getQuery()->getResult();

                foreach ($authors as $keyAuthor => $author) {
                    $paramName = 'a_' . $keyAuthor;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.authors'));
                    $queryBuilder->setParameter($paramName, $author);
                }
                break;
            case 'parody':
                $repoParody = $em->getRepository(Parody::class);
                $parodies = $repoParody->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoParody->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $query)->getQuery()->getResult();

                foreach ($parodies as $keyParody => $parody) {
                    $paramName = 'p_' . $keyParody;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.parodies'));
                    $queryBuilder->setParameter($paramName, $parody);
                }
                break;
            default:
                return $this->findLatest();
        }

        $queryBuilder->andWhere($orStatements)->orderBy('p.id', 'DESC');

        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    /**
     * @return Manga[]
     */
    public function findBySearchQueryAdvanced(
        string $rawQuery,
        string $tagsQuery,
        string $languesQuery,
        string $orderBy,
        bool   $isOld = false,
        int    $page = 1
    ): Paginator
    {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $tagsQuerySanitized = $this->sanitizeSearchQuery($tagsQuery);

        $em = $this->getEntityManager();
        $repoLanguage = $em->getRepository(Language::class);
        $repoTag = $em->getRepository(Tag::class);

        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.isCorrupted = false');

        if (!$isOld) {
            $queryBuilder->andWhere('p.isOld = false');
        }

        $orStatements = $queryBuilder->expr()->andX();
        if ($query != '') {
            $orStatements->add($queryBuilder->expr()->like('p.title', ':title'));
            $queryBuilder->setParameter(':title', '%' . $query . '%');
        }

        foreach (explode(',', $tagsQuerySanitized) as $tagQuerySanitized) {
            $tag = $repoTag->findOneByName($tagQuerySanitized);
            if ($tag) {
                $paramName = 'ta_' . $tag->getId();
                $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.tags'));
                $queryBuilder->setParameter($paramName, $tag);
            }
        }

        if ($languesQuery != '*') {
            $language = $repoLanguage->findOneByName($languesQuery);
            if ($language) {
                $paramName = 'l_' . $language->getId();
                $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.languages'));
                $queryBuilder->setParameter($paramName, $language);
            }
        }

        $queryBuilder->andWhere($orStatements);
        match ($orderBy) {
            'old_to_recent' => $queryBuilder->orderBy('p.id', 'ASC'),
            'increase_view' => $queryBuilder->orderBy('p.countViews', 'ASC'),
            'decrease_view' => $queryBuilder->orderBy('p.countViews', 'DESC'),
            'increase_count_page' => $queryBuilder->orderBy('p.countPages', 'ASC'),
            'decrease_count_page' => $queryBuilder->orderBy('p.countPages', 'DESC'),
            default => $queryBuilder->orderBy('p.id', 'DESC')
        };
        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    public function countByTag(Tag $tag): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.tags', 't')
            ->andWhere('t.id = :tag_id')
            ->setParameter('tag_id', $tag->getId())
            ->select('count(t.id)')
            ->getQuery();

        $queryBuilder->setResultCacheId('mangas_tag_count_' . $tag->getId());
        $queryBuilder->setResultCacheLifeTime(3600);

        return $queryBuilder->getSingleScalarResult();
    }

    public function countByLanguage(Language $language): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.languages', 'l')
            ->andWhere('l.id = :language_id')
            ->setParameter('language_id', $language->getId())
            ->select('count(l.id)')
            ->getQuery();

        $queryBuilder->setResultCacheId('mangas_language_count_' . $language->getId());
        $queryBuilder->setResultCacheLifeTime(3600);

        return $queryBuilder->getSingleScalarResult();
    }

    public function countByParody(Parody $parody): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.parodies', 'pp')
            ->andWhere('pp.id = :parody_id')
            ->setParameter('parody_id', $parody->getId())
            ->select('count(pp.id)')
            ->getQuery();

        $queryBuilder->setResultCacheId('mangas_parody_count_' . $parody->getId());
        $queryBuilder->setResultCacheLifeTime(3600);

        return $queryBuilder->getSingleScalarResult();
    }

    public function countByAuthor(Author $author): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.authors', 'a')
            ->andWhere('a.id = :author_id')
            ->setParameter('author_id', $author->getId())
            ->select('count(a.id)')
            ->getQuery();

        $queryBuilder->setResultCacheId('mangas_author_count_' . $author->getId());
        $queryBuilder->setResultCacheLifeTime(3600);

        return $queryBuilder->getSingleScalarResult();
    }

    public function findByAuthor(Author $author): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.authors', 'a')
            ->where('a.id = :author_id')
            ->setParameter('author_id', $author->getId())
            ->andWhere('p.isCorrupted = false')
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
            ->join('p.tags', 't')
            ->where('t.id = :tag_id')
            ->setParameter('tag_id', $tag->getId())
            ->andWhere('p.isCorrupted = false')
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
            ->where('p.isOld = ' . (int)$onlyOld)
            ->andWhere('p.isCorrupted = true')
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
