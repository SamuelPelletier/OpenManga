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

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/blog")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, methods={"GET"}, name="blog_index")
     * @Route("/rss.xml", defaults={"page": "1", "_format"="xml"}, methods={"GET"}, name="blog_rss")
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods={"GET"}, name="blog_index_paginated")
     * @Cache(smaxage="10")
     *
     * NOTE: For standard formats, Symfony will also automatically choose the best
     * Content-Type header for the response.
     * See https://symfony.com/doc/current/quick_tour/the_controller.html#using-formats
     */
    public function index(Request $request, int $page, string $_format, MangaRepository $mangas, TagRepository $tags): Response
    {
        $tag = null;
        if ($request->query->has('tag')) {
            $tag = $tags->findOneBy(['name' => $request->query->get('tag')]);
        }
        $latestMangas = $mangas->findLatest($page, $tag);
        dump($latestMangas);
        // Every template name also has two extensions that specify the format and
        // engine for that template.
        // See https://symfony.com/doc/current/templating.html#template-suffix
        return $this->render('blog/index.'.$_format.'.twig', ['mangas' => $latestMangas]);
    }

    /**
     * @Route("/mangas/{id}", methods={"GET"}, name="blog_manga")
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

        return $this->render('blog/manga_show.html.twig', ['manga' => $manga]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="blog_search")
     */
    public function search(Request $request, MangaRepository $mangas): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->render('blog/search.html.twig');
        }

        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);
        $foundMangas = $mangas->findBySearchQuery($query, $limit);

        $results = [];
        foreach ($foundMangas as $manga) {
            $results[] = [
                'title' => htmlspecialchars($manga->getTitle(), ENT_COMPAT | ENT_HTML5),
                'date' => $manga->getPublishedAt()->format('M d, Y'),
                'author' => htmlspecialchars($manga->getAuthor()->getName(), ENT_COMPAT | ENT_HTML5),
                'title' => htmlspecialchars($manga->getTitle(), ENT_COMPAT | ENT_HTML5),
                'url' => $this->generateUrl('blog_manga', ['link' => $manga->getLink()]),
            ];
        }

        return $this->json($results);
    }
}
