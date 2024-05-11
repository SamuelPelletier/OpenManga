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
     * @Route("/donate", name="donate")
     *
     */
    public function donate(Request $request): Response
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.commerce.coinbase.com/charges',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "local_price": {
        "amount": "1",
        "currency": "USD"
    },
    "pricing_type": "no_price",
    "name": "' . $_ENV['APP_NAME'] . '",
    "description": "Support & Advantage",
    "metadata": {
        "id": 1
    }
}',
            CURLOPT_HTTPHEADER => array(
                'X-CC-Api-Key: ' . $_ENV['COINBASE_API_KEY'],
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response);

        return $this->render('donate.html.twig', ['url' => $response->data->hosted_url]);
    }

    /**
     * @Route("/donate/webhook", name="donate-webhook")
     *
     */
    public function donateWebhook(Request $request): Response
    {
        dd($request);
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
     * @Route("/sitemap-index.xml", name="sitemap-index", defaults={"_format"="xml"})
     */
    public function sitemapIndex(Request $request, MangaRepository $mangaRepository)
    {
        $urls = array();
        for ($i = 1; $i <= ceil($mangaRepository->findOneBy([], ['id' => 'desc'])->getId() / 20000); $i++) {
            $urls[] = $this->generateUrl('sitemap') . '?c=' . $i;
        }

        $response = new Response(
            $this->renderView('sitemap_index.html.twig', ['urls' => $urls]),
            200
        );
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
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

        $c = $request->get('c', 1);
        $c--;
        if ($c == 0) {
// On ajoute les URLs "statiques"
            $urls[] = ['loc' => $this->generateUrl('index'), 'changefreq' => 'always'];
            $urls[] = ['loc' => $this->generateUrl('app_register'), 'changefreq' => 'yearly'];
            $urls[] = ['loc' => $this->generateUrl('app_login'), 'changefreq' => 'yearly'];
            $urls[] = ['loc' => $this->generateUrl('about'), 'changefreq' => 'yearly'];
            $urls[] = ['loc' => $this->generateUrl('tags'), 'changefreq' => 'yearly'];
        }

// On ajoute les URLs dynamiques des articles dans le tableau
        $mangas = $mangaRepository->createQueryBuilder('m')
            ->where('m.id >= ' . $c * 20000)
            ->andWhere('m.id < ' . ($c * 20000) + 20000)
            ->getQuery()->getResult();
        foreach ($mangas as $manga) {
            $urls[] = [
                'loc' => $this->generateUrl('manga', [
                    'id' => $manga->getId()
                ]),
                'id' => $manga->getId(),
                'lastmod' => $manga->getPublishedAt()->format('Y-m-d'),
                'changefreq' => 'yearly'
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
