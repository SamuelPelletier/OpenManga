<?php
/**
 * Created by PhpStorm.
 * User: samue
 * Date: 27/07/2020
 * Time: 16:47
 */

namespace App\Service;


use App\Entity\Manga;
use App\Entity\Tag;
use App\Repository\MangaRepository;

class MangaService
{
    private $mangaRepository;

    public function __construct(MangaRepository $mangaRepository)
    {
        $this->mangaRepository = $mangaRepository;
    }

    public function getRecommendationByManga(Manga $manga): array
    {
        $authors = $manga->getAuthors();
        $potentialMangas = [];

        foreach ($authors as $author) {
            $potentialMangas = array_merge($potentialMangas,
                $this->mangaRepository->findByAuthor($author));
        }

        $potentialMangas = array_unique($potentialMangas, SORT_REGULAR);

        $countMangaTags = $manga->getTags()->count();

        $potentialMangasWithLevenshtein = [];
        $potentialMangasWithTags = [];

        foreach ($potentialMangas as $key => $potentialManga) {
            if ($potentialManga->getId() === $manga->getId()) {
                unset($potentialMangas[$key]);
            } else {
                if (levenshtein(strtoupper($potentialManga->getTitle()),
                        strtoupper($manga->getTitle())) <= 5) {
                    $potentialMangasWithLevenshtein[] = $potentialManga;
                } else {
                    // Almost 50% of the same tags
                    if (count(array_intersect($potentialManga->getTags()->toArray(),
                            $manga->getTags()->toArray())) > $countMangaTags / 2) {
                        $potentialMangasWithTags[] = $potentialManga;
                    }
                }
            }
            if (count($potentialMangasWithTags) + count($potentialMangasWithLevenshtein) === 4) {
                break;
            }
        }

        $results = array_merge($potentialMangasWithLevenshtein, $potentialMangasWithTags);

        $countResults = count($results);

        // Need 4 recommended mangas
        if ($countResults <= 10 && $manga->getTags()->count() > 0) {
            // Get max 5 mangas if we find the initial manga
            $mangasRecommendedByTag = $this->mangaRepository->findByTag($manga->getTags()->first(), 11 - $countResults);
            foreach ($mangasRecommendedByTag as $key => $mangaRecommendedByTag) {
                if ($mangaRecommendedByTag->getId() === $manga->getId()) {
                    unset($mangasRecommendedByTag[$key]);
                }
            }
            // Remove useless manga and keep up 4 mangas
            if (count($mangasRecommendedByTag) + $countResults > 10) {
                unset($mangasRecommendedByTag[count($mangasRecommendedByTag) - 1]);
            }
            $results = array_merge($results, $mangasRecommendedByTag);
        }

        return $results;
    }
}