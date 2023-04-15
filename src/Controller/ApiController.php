<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\LanguageRepository;
use App\Repository\MangaRepository;
use App\Repository\ParodyRepository;
use App\Repository\TagRepository;
use App\Service\MangaService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;

class ApiController extends BaseController
{
    /**
     * @Route("/api/", defaults={"page": "1"}, methods={"GET"}, name="mangas")
     * @Route("/api/page/{page<[1-9]\d*>}", methods={"GET"}, name="mangas_paginated")
     * @Cache(smaxage="10")
     *
     */
    public function mangas()
    {
        return $this->json(['success' => true]);
    }
}
