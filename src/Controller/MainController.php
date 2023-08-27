<?php
/**
 * Created by PhpStorm.
 * User: samue
 * Date: 10/06/2019
 * Time: 19:54
 */

namespace App\Controller;


use App\Repository\MangaRepository;
use App\Repository\TagRepository;
use App\Utils\TagDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MainController
 * @package App\Controller
 */
class MainController extends AbstractController
{
    /**
     * @Route("/about", name="about")
     *
     */
    public function about(): Response
    {
        return $this->render('about.html.twig');
    }

    /**
     * @Route("/disclaimer", methods={"POST","GET"},name="disclaimer")
     *
     */
    public function disclaimer(Request $request): Response
    {
        $finalUrl = $request->getSession()->get('final_url');

        if (strstr($finalUrl, "disclaimer") != false || $finalUrl == null || preg_match("/\/[a-z]{2}\//",
                $finalUrl) == false) {
            $finalUrl = $this->generateUrl('index');
        }

        $query = parse_url($finalUrl, PHP_URL_QUERY);

        if ($query) {
            $finalUrl .= '&disclaimer=1';
        } else {
            $finalUrl .= '?disclaimer=1';
        }
        $request->getSession()->remove('final_url');

        return $this->render('disclaimer.html.twig', ['finalUrl' => $finalUrl]);
    }

    /**
     * @Route("/sitemap.xml", name="sitemap", defaults={"_format"="xml"})
     */
    public function sitemap(Request $request, MangaRepository $mangaRepository)
    {
        // Nous récupérons le nom d'hôte depuis l'URL
        $hostname = $request->getSchemeAndHttpHost();

        // On initialise un tableau pour lister les URLs
        $urls = [];

// On ajoute les URLs "statiques"
        $urls[] = ['loc' => $this->generateUrl('index'), 'changefreq' => 'always'];
        $urls[] = ['loc' => $this->generateUrl('app_register'), 'changefreq' => 'yearly'];
        $urls[] = ['loc' => $this->generateUrl('app_login'), 'changefreq' => 'yearly'];
        $urls[] = ['loc' => $this->generateUrl('about'), 'changefreq' => 'yearly'];
        $urls[] = ['loc' => $this->generateUrl('tags'), 'changefreq' => 'yearly'];

// On ajoute les URLs dynamiques des articles dans le tableau
        foreach ($mangaRepository->findAll() as $manga) {
            $images = array();
            if (is_dir('media/' . $manga->getId() . '/')) {
                $finder = new Finder();
                $finder->files()->in('media/' . $manga->getId() . '/');
                foreach ($finder as $file) {
                    // dumps the relative path to the file
                    array_push($images, $file->getRelativePathname());
                }
            }
            sort($images);

            $urls[] = [
                'loc' => $this->generateUrl('manga', [
                    'id' => $manga->getId()
                ]),
                'id' => $manga->getId(),
                'lastmod' => $manga->getPublishedAt()->format('Y-m-d'),
                'changefreq' => 'yearly',
                'images' => $images
            ];

        }

        $response = new Response(
            $this->renderView('sitemap.html.twig', ['urls' => $urls,
                'hostname' => $hostname]),
            200
        );
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }
}
