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

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, methods={"GET"}, name="index")
     * @Route("/page/{page<[1-9]\d*>}", methods={"GET"}, name="index_paginated")
     * @Cache(smaxage="10")
     *
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     * See https://symfony.com/doc/current/quick_tour/the_controller.html#using-formats
     */
    public function index(Request $request, int $page,  MangaRepository $mangas, TagRepository $tags): Response
    {
        $tag = null;
        if ($request->query->has('tag')) {
            $tag = $tags->findOneBy(['name' => $request->query->get('tag')]);
        }
        $latestMangas = $mangas->findLatest($page, $tag);
        // Every template name also has two extensions that specify the format and
        // engine for that template.
        // See https://symfony.com/doc/current/templating.html#template-suffix
        return $this->render('index.html.twig', ['mangas' => $latestMangas]);
    }

    /**
     * @Route("/mangas/{id}", methods={"GET"}, name="manga")
     *
     * NOTE: The $manga controller argument is automatically injected by Symfony
     * after performing a database query looking for a Manga with the 'id'
     * value given in the route.
     * See https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
     */
    public function mangaShow(Manga $manga): Response
    {
        // Symfony's 'dump()' function is an improved version of PHP's 'var_dump()' but
        // it's not available in the 'prod' environment to prevent leaking sensitive information.
        // It can be used both in PHP files and Twig templates, but it requires to
        // have enabled the DebugBundle. Uncomment the following line to see it in action:
        //
        // dump($manga, $this->getUser(), new \DateTime());
        $images = array();
        if (is_dir ('media/'.$manga->getId().'/')){
            $finder = new Finder();
            $finder->files()->in('media/'.$manga->getId().'/');
            foreach ($finder as $file) {
                // dumps the relative path to the file
                array_push($images,$file->getRelativePathname());
            }
        }
        return $this->render('manga_show.html.twig', ['manga' => $manga, 'images'=> $images]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="search")
     * @Route("/search/page/{page<[1-9]\d*>}", methods={"GET"}, name="search_paginated")
     */
    public function search(Request $request, int $page = 1, MangaRepository $mangas): Response
    {
        // No query parameter
        $foundMangas = null;
        if($request->query->get('q') !== null && $request->query->get('q') == ''){
            return $this->redirectToRoute('index');
        }else if ($request->query->get('q') != '') {
            $query = $request->query->get('q', '');
            $foundMangas = $mangas->findBySearchQuery($query, $page);
        }

        return $this->render('search.html.twig',['mangas' => $foundMangas]);
    }

    /**
     * @Route("/download/{id}", methods={"GET"}, name="download")
     */
    public function mangaDownload(Manga $manga): Response
    {
        if (is_dir ('media/'.$manga->getId().'/')){
            $zipName = 'Documents_' . time() . ".zip";
            $images = array();
            $files = array();
            $finder = new Finder();
            $finder->files()->in('media/'.$manga->getId().'/');
            foreach ($finder as $file) {
                array_push($files, $file);
            }

            $zip = new \ZipArchive();
            $zip->open($zipName,  \ZipArchive::CREATE);
            foreach ($files as $f) {
                $zip->addFromString(basename($f),  file_get_contents($f));
            }
            $zip->close();
            $response = new Response(file_get_contents($zipName));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipName));
            return $response;
        }else{
            throw $this->createNotFoundException('Sorry download file doesn\'t exist');
        }

    }
}
