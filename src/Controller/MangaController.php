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

use App\Entity\Comment;
use App\Entity\Manga;
use App\Events;
use App\Form\CommentType;
use App\Repository\MangaRepository;
use App\Repository\TagRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
        MangaRepository $mangas
    ): Response {
        $latestMangas = $mangas->findLatest($page);

        return $this->render('index.html.twig', ['mangas' => $latestMangas]);
    }

    /**
     * @Route("/mangas/{id}", methods={"GET"}, name="manga")
     *
     */
    public function mangaShow(Manga $manga): Response
    {
        $images = array();
        if (is_dir('media/' . $manga->getId() . '/')) {
            $finder = new Finder();
            $finder->files()->in('media/' . $manga->getId() . '/');
            foreach ($finder as $file) {
                // dumps the relative path to the file
                array_push($images, $file->getRelativePathname());
            }
        }
        return $this->render('manga_show.html.twig', ['manga' => $manga, 'images' => $images]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="search")
     * @Route("/search/page/{page<[1-9]\d*>}", methods={"GET"}, name="search_paginated")
     */
    public function search(Request $request, MangaRepository $mangas, int $page = 1): Response
    {
        // No query parameter
        $foundMangas = null;
        if ($request->query->get('q') !== null && $request->query->get('q') == '') {
            return $this->redirectToRoute('index');
        } else {
            if ($request->query->get('q') != '') {
                $query = $request->query->get('q', '');
                $foundMangas = $mangas->findBySearchQuery($query, $page);
            }
        }

        return $this->render('search.html.twig', ['mangas' => $foundMangas]);
    }

    /**
     * @Route("/download/{id}", methods={"GET"}, name="download")
     */
    public function mangaDownload(Manga $manga): Response
    {
        if (is_dir('media/' . $manga->getId() . '/')) {
            $zipName = htmlspecialchars_decode($manga->getTitle(), ENT_QUOTES) . ".zip";
            $zipName = str_replace(['|', '/', '\\'], '', $zipName);
            $images = array();
            $files = array();
            $finder = new Finder();
            $finder->files()->in('media/' . $manga->getId() . '/');
            foreach ($finder as $file) {
                array_push($files, $file);
            }

            $zip = new \ZipArchive();
            $zip->open($zipName, \ZipArchive::CREATE);
            foreach ($files as $f) {
                $zip->addFromString(basename($f), file_get_contents($f));
            }
            $zip->close();
            $response = new Response(file_get_contents($zipName));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipName));
            return $response;
        } else {
            throw $this->createNotFoundException('Sorry this file doesn\'t exist');
        }

    }

    /**
     * @Route("/disclaimer", name="disclaimer")
     */
    public function disclaimer(): Response
    {
        return $this->render('disclaimer.html.twig');
    }
}
