<?php

namespace App\Controller;

use App\Repository\ParodyRepository;
use App\Repository\TagRepository;
use App\Utils\TagDTO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/parodies")]
class ParodyController extends AbstractController
{
    #[Route("/search", methods: ['GET'], name: 'parody_search')]
    public function search(ParodyRepository $parodyRepository): Response
    {
        return $this->json(['response' => true, 'data' => $parodyRepository->findByNameWithStart($_GET['q'])]);
    }
}
