<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Language;
use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\LanguageRepository;
use App\Repository\MangaRepository;
use App\Repository\ParodyRepository;
use App\Repository\TagRepository;
use App\Service\MangaService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/")
 *
 */
class MangaController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1"}, methods={"GET"}, name="index")
     * @Route("/page/{page<[1-9]\d*>}", methods={"GET"}, name="index_paginated")
     * @Cache(smaxage="10")
     *
     */
    public function index(
        int $page,
        Request $request,
        MangaRepository $mangas
    ): Response {
        $isSortByViews = false;
        if ($request->getSession()->get('sort') != null) {
            $isSortByViews = $request->getSession()->get('sort');
        }
        $latestMangas = $mangas->findLatest($page, $isSortByViews);

        if ($request->isXmlHttpRequest()) {
            if (count($latestMangas->getQuery()->getArrayResult()) === 0) {
                return $this->render('manga_ending.html.twig');
            }
            return $this->render('manga_index.html.twig', ['mangas' => $latestMangas]);
        }
        return $this->render('index.html.twig', ['mangas' => $latestMangas]);
    }

    /**
     * @Route("/json", methods={"GET"}, name="index_json")
     */
    public function indexJSON(
        TagRepository $tagRepository,
        AuthorRepository $authorRepository,
        LanguageRepository $languageRepository,
        ParodyRepository $parodyRepository
    ): Response {
        $tags = $tagRepository->findAll();
        $authors = $authorRepository->findAll();
        $languages = $languageRepository->findAll();
        $parodies = $parodyRepository->findAll();
        return new JsonResponse(array_merge($tags, $authors, $languages, $parodies));
    }

    /**
     * @Route("/mangas/{id}", methods={"GET"}, name="manga")
     *
     */
    public function mangaShow(
        Manga $manga,
        MangaRepository $mangaRepository,
        Request $request,
        MangaService $mangaService,
        EntityManagerInterface $entityManager
    ): Response {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $images = array();
        if (is_dir('media/' . $manga->getId() . '/')) {
            $finder = new Finder();
            $finder->files()->in('media/' . $manga->getId() . '/');
            foreach ($finder as $file) {
                // dumps the relative path to the file
                array_push($images, $file->getRelativePathname());
            }
        }

        $mangaView = explode(',', $request->getSession()->get('manga_view'));
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
                'mangas_recommended' => $mangasRecommended
            ]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="search")
     * @Route("/search/page/{page<[1-9]\d*>}", methods={"GET"}, name="search_paginated")
     */
    public function search(
        Request $request,
        MangaRepository $mangas,
        int $page = 1
    ): Response {
        // No query parameter
        $foundMangas = null;
        $isSortByViews = $request->query->get('sort') != null ? true : false;
        $request->getSession()->set('sort', $isSortByViews);

        if ($request->query->get('q') !== null && $request->query->get('q') == '') {
            return $this->redirectToRoute('index');
        } else {
            if ($request->query->get('q') != '') {
                $query = $request->query->get('q', '');
                $isStrict = $request->query->get('s', false);
                $foundMangas = $mangas->findBySearchQuery($query, $page, $isSortByViews, $isStrict);
            }
        }

        return $this->render('search.html.twig', ['mangas' => $foundMangas]);
    }

    /**
     * @Route("/download/{id}", methods={"GET"}, name="download")
     */
    public function mangaDownload(
        Manga $manga
    ): Response {

        $entityManager = $this->getDoctrine()->getManager();
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if (is_dir('media/' . $manga->getId() . '/')) {
            $zipName = htmlspecialchars_decode($manga->getTitle(), ENT_QUOTES) . ".zip";
            $zipName = str_replace(['|', '/', '\\'], '', $zipName);
            $files = array();
            $finder = new Finder();
            $finder->files()->in('media/' . $manga->getId() . '/');
            foreach ($finder as $file) {
                if (preg_match("/\.jpg$/", $file->getFilename())) {
                    array_push($files, $file);
                }
            }

            $zip = new \ZipArchive();
            $zip->open($zipName, \ZipArchive::CREATE);
            foreach ($files as $f) {
                $zip->addFromString(basename($f), file_get_contents($f));
            }
            $zip->close();
            $response = new BinaryFileResponse($zipName);
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipName));
            $response->deleteFileAfterSend(true);

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

    /**
     * @Route("/favorite/{id}/add", methods={"POST"}, name="add_favorite")
     */
    public function addFavorite(Manga $manga)
    {
        $entityManager = $this->getDoctrine()->getManager();
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $user->addFavoriteManga($manga);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['response' => true]);
    }

    /**
     * @Route("/favorite/{id}/remove", methods={"POST"}, name="remove_favorite")
     */
    public function removeFavorite(Manga $manga)
    {
        $entityManager = $this->getDoctrine()->getManager();
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
