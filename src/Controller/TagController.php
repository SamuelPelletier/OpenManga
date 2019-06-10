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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    /**
     * @Route("/tags", name="tags")
     *
     */
    public function index(TagRepository $tagRepository, MangaRepository $mangaRepository): Response
    {
        $tags = $tagRepository->findAll();
        return $this->render('tags.html.twig', ['tags' => $tags, 'mangaRepository' => $mangaRepository]);
    }
}