<?php

namespace App\Controller;

use App\Repository\TagRepository;
use App\Utils\TagDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TagController extends AbstractController
{
    #[Route("/tags", methods: ['GET'], name: 'tags')]
    public function index(Request $request, TagRepository $tagRepository): Response
    {

        $queryLetter = 'a';
        if ($request->query->get('qt') !== null && $request->query->get('qt') !== '') {
            $queryLetter = $request->query->get('qt', 'a');
        }

        $tags = $tagRepository->findByFirstLetter($queryLetter);
        $tagsDTO = [];
        foreach ($tags as $tag) {
            $tagsDTO[] = new TagDTO($tag[0], $tag['total']);
        }

        $tagsPopular = $tagRepository->findWithMangaCounter();

        $tagsPopularDTO = [];
        foreach ($tagsPopular as $tag) {
            $tagsPopularDTO[] = new TagDTO($tag[0], $tag['total']);
        }

        $tagsDTOsort = $tagsPopularDTO;
        usort($tagsDTOsort, function (TagDTO $tagDTO1, TagDTO $tagDTO2) {
            return $tagDTO1->getCountUse() < $tagDTO2->getCountUse();
        });

        return $this->render('tags.html.twig',
            ['tagsDTO' => $tagsDTO, 'tagsDTOsort' => $tagsDTOsort]);
    }
}
