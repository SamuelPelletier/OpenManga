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
            ->where('p.isCorrupted = false')->orderBy('p.id', 'DESC');

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
            ->where('p.isCorrupted = false');

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
                    ->from($repoTag->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($tags as $keyTag => $tag) {
                    $paramName = 'ta_' . $keyTag;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.tags'));
                    $queryBuilder->setParameter($paramName, $tag);
                }

                $languages = $repoLanguage->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoLanguage->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($languages as $keyLanguage => $language) {
                    $paramName = 'l_' . $keyLanguage;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.languages'));
                    $queryBuilder->setParameter($paramName, $language);
                }

                $parodies = $repoParody->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoParody->getEntityName(), 'a')->where('a.name like :name')
                    ->setParameter('name', $term)->getQuery()->getResult();

                foreach ($parodies as $keyParody => $parody) {
                    $paramName = 'p_' . $keyParody;
                    $orStatements->add($queryBuilder->expr()->isMemberOf(':' . $paramName, 'p.parodies'));
                    $queryBuilder->setParameter($paramName, $parody);
                }

                $authors = $repoAuthor->getEntityManager()->createQueryBuilder()
                    ->select('a')
                    ->from($repoAuthor->getEntityName(), 'a')->where('a.name like :name')
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

        $queryBuilder->andWhere($orStatements)->orderBy('p.id', 'DESC');

        return $this->createPaginator($queryBuilder->getQuery(), $page);
    }

    public function countByTag(Tag $tag): int
    {
        return $this->createQueryBuilder('p')
            ->join('p.tags', 't')
            ->andWhere('t.id = :tag_id')
            ->setParameter('tag_id', $tag->getId())
            ->select('count(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByLanguage(Language $language): int
    {
        return $this->createQueryBuilder('p')
            ->join('p.languages', 'l')
            ->andWhere('l.id = :language_id')
            ->setParameter('language_id', $language->getId())
            ->select('count(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByParody(Parody $parody): int
    {
        return $this->createQueryBuilder('p')
            ->join('p.parodies', 'pp')
            ->andWhere('pp.id = :parody_id')
            ->setParameter('parody_id', $parody->getId())
            ->select('count(pp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByAuthor(Author $author): int
    {
        return $this->createQueryBuilder('p')
            ->join('p.authors', 'a')
            ->andWhere('a.id = :author_id')
            ->setParameter('author_id', $author->getId())
            ->select('count(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
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
