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

class TagController extends AbstractController
{
    /**
     * @Route("/tags", name="tags")
     *
     */
    public function index(TagRepository $tagRepository, MangaRepository $mangaRepository): Response
    {
        $tags = $tagRepository->findBy([], ['name' => 'asc']);
        $tagsDTO = [];
        foreach ($tags as $tag) {
            $tagsDTO[] = new TagDTO($tag, $mangaRepository->countByTag($tag));
        }
        $tagsDTOsort = $tagsDTO;
        usort($tagsDTOsort, function (TagDTO $tagDTO1, TagDTO $tagDTO2) {
            return $tagDTO1->getCountUse() < $tagDTO2->getCountUse();
        });
        //dd($tagsDTOsort);
        return $this->render('tags.html.twig',
            ['tagsDTO' => $tagsDTO, 'tagsDTOsort' => $tagsDTOsort]);
    }
}