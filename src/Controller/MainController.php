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
}
