<?php

namespace App\Controller;

use App\Entity\Language;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\LanguageRepository;
use App\Repository\MangaRepository;
use App\Service\MangaService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;

#[Route("/")]
class MangaController extends BaseController
{
    #[Route("/", defaults: ['page' => 1], methods: ['GET'], name: 'index')]
    #[Route("/page/{page<[1-9]\d*>}", methods: ['GET'], name: 'index_paginated')]
    #[Cache(maxage: 10)]
    public function index(
        int             $page,
        Request         $request,
        MangaRepository $mangas
    ): Response
    {
        $latestMangas = $mangas->findLatest($page);
        if ($request->isXmlHttpRequest()) {
            return $this->render('manga_index.html.twig', ['mangas' => $latestMangas]);
        }
        return $this->render('index.html.twig', ['mangas' => $latestMangas]);
    }

    #[Route("/trending", defaults: ['page' => 1], methods: ['GET'], name: 'index_trending')]
    #[Route("/trending/page/{page<[1-9]\d*>}", methods: ['GET'], name: 'index_trending_paginated')]
    #[Cache(maxage: 10)]
    public function trending(
        int             $page,
        Request         $request,
        MangaRepository $mangas
    ): Response
    {
        $latestMangas = $mangas->findTrending($page);
        if ($request->isXmlHttpRequest()) {
            return $this->render('manga_index.html.twig', ['mangas' => $latestMangas]);
        }
        return $this->render('trending.html.twig', ['mangas' => $latestMangas]);
    }

    #[Route("/mangas/{id}", methods: ['GET'], name: 'manga')]
    public function mangaShow(
        Manga                  $manga,
        MangaRepository        $mangaRepository,
        Request                $request,
        MangaService           $mangaService,
        EntityManagerInterface $entityManager
    ): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if ($manga->isCorrupted()) {
            return $this->render('bundles/TwigBundle/Exception/error_404.html.twig');
        }

        // Add user permission
        if ((($manga->isOld() || $manga->isBlocked()) && !$user?->isPatreonAllow(1)) and ($manga->isOld() && !$user->isUnlockOldManga())) {
            return $this->render('bundles/TwigBundle/Exception/error_403.html.twig');
        }

        $images = array();
        for ($i = 1; $i < $manga->getCountPages(); $i++) {
            $images[] = str_pad($i, 3, 0, STR_PAD_LEFT) . '.jpg';
        }

        $mangaView = explode(',', $request->getSession()->get('manga_view', ''));
        // Check in the session if this manga is already view
        if (!in_array($manga->getId(), $mangaView)) {
            $request->getSession()->set('manga_view',
                $request->getSession()->get('manga_view') . ',' . $manga->getId());
            $manga->setCountViews($manga->getCountViews() + 1);
            $entityManager->persist($manga);
            $entityManager->flush();
        }

        // User is logged in
        if ($this->isGranted('ROLE_USER')) {
            $user->addLastMangasRead($manga);
            if (!in_array($manga->getId(), $mangaView)) {
                $user->incrementCountMangasRead();
            }
            $entityManager->persist($user);
            $entityManager->flush();
        }

        $mangasRecommended = $mangaService->getRecommendationByManga($manga);

        return $this->render('manga_show.html.twig',
            [
                'manga' => $manga,
                'images' => $images,
                'mangaRepository' => $mangaRepository,
                'mangas_recommended' => $mangasRecommended,
            ]);
    }

    #[Route("/mangas/{id}/recommended", methods: ['GET'], name: 'manga_recommended')]
    public function mangaRecommended(
        Manga        $manga,
        Request      $request,
        MangaService $mangaService,
    ): Response
    {
        $maxResult = $request->query->get('max', 6);
        $maxResult = $maxResult > 10 || $maxResult < 0 ? 6 : $maxResult;
        $mangasRecommended = array_slice($mangaService->getRecommendationByManga($manga), 0, $maxResult);
        return $this->json(['data' => $mangasRecommended]);
    }


    #[Route("/search", methods: ['GET'], name: 'search')]
    #[Route("/search/page/{page<[1-9]\d*>}", methods: ['GET'], name: 'search_paginated')]
    public function search(
        Request         $request,
        MangaRepository $mangas,
        int             $page = 1
    ): Response
    {
        // No query parameter
        $foundMangas = null;

        if ($request->query->get('q') !== null && $request->query->get('q') == '') {
            return $this->redirectToRoute('index');
        } else {
            if ($request->query->get('q') != '') {
                $query = $request->query->get('q', '');
                if ($type = $request->query->get('t')) {
                    $foundMangas = $mangas->findByStrictTypeSearchQuery($query, $page, $type);
                } else {
                    $foundMangas = $mangas->findBySearchQuery($query, $page);
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            if (count($foundMangas->getQuery()->getArrayResult()) === 0) {
                return $this->render('manga_ending.html.twig');
            }
            return $this->render('manga_index.html.twig', ['mangas' => $foundMangas]);
        }

        return $this->render('search.html.twig', ['mangas' => $foundMangas]);
    }

    #[Route("/advanced_search", methods: ['GET'], name: 'advanced_search')]
    #[Route("/advanced_search/page/{page<[1-9]\d*>}", methods: ['GET'], name: 'advanced_search_paginated')]
    public function advancedSearch(
        Request            $request,
        MangaRepository    $mangas,
        LanguageRepository $languageRepository,
        int                $page = 1
    ): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        // No query parameter
        $languages = $languageRepository->createQueryBuilder('l')
            ->where('l.name in (:language_allow)')
            ->setParameter('language_allow', array_keys(Language::ISO_CODE))
            ->orderBy('l.name', 'ASC')->getQuery()->getResult();

        $query = $request->query->get('q', '');
        $tagQuery = $request->query->get('t', '');
        $languesQuery = $request->query->get('language', 'all');
        $orderBy = $request->query->get('sort', 'recent_to_old');
        $isOld = 'off';
        if ($user?->isUnlockOldManga()) {
            $isOld = $request->query->get('is_old', 'off');
        }
        $foundMangas = $mangas->findBySearchQueryAdvanced($query, $tagQuery, $languesQuery, $orderBy, $isOld == 'on', $page);
        return $this->render('advanced_search.html.twig', ['mangas' => $foundMangas, 'languages' => $languages]);
    }

    #[Route("/download/{id}", methods: ['GET'], name: 'download')]
    public function mangaDownload(
        Manga                  $manga,
        EntityManagerInterface $entityManager
    ): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $folder = $manga->isOld() ? 'media_old' : 'media';
        if (is_dir($folder . '/' . $manga->getId() . '/')) {

            $zipFolder = 'media_zipped/';

            $zipName = htmlspecialchars_decode($manga->getTitle(), ENT_QUOTES) . ".zip";
            $zipName = str_replace(['|', '/', '\\'], '', $zipName);
            if (!file_exists($zipFolder . $zipName)) {
                $files = array();
                $finder = new Finder();
                $finder->files()->in($folder . '/' . $manga->getId() . '/');
                foreach ($finder as $file) {
                    if (preg_match("/\.jpg$/", $file->getFilename())) {
                        array_push($files, $file);
                    }
                }

                $zip = new \ZipArchive();
                $zip->open($zipFolder . $zipName, \ZipArchive::CREATE);
                foreach ($files as $f) {
                    $zip->addFromString(basename($f), file_get_contents($f));
                }
                $zip->close();
            }

            $response = new BinaryFileResponse($zipFolder . $zipName);
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipFolder . $zipName));

            if ($user) {
                $user->incrementCountMangasDownload();
                $entityManager->persist($user);
                $entityManager->flush();
            }

            return $response;
        } else {
            throw $this->createNotFoundException('Sorry this file doesn\'t exist');
        }

    }

    #[Route("/favorite/{id}/add", methods: ['POST'], name: 'add_favorite')]
    public function addFavorite(Manga $manga, EntityManagerInterface $entityManager)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $user->addFavoriteManga($manga);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['response' => true]);
    }

    #[Route("/favorite/{id}/remove", methods: ['POST'], name: 'remove_favorite')]
    public function removeFavorite(Manga $manga, EntityManagerInterface $entityManager)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $user->removeFavoriteManga($manga);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['response' => true]);
    }
}
