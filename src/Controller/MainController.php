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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MainController
 * @package App\Controller
 */
class MainController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     *
     */
    public function contact(): Response
    {
        return $this->render('contact.html.twig');
    }
}