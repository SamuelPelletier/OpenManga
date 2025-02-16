<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PatreonService;
use Doctrine\ORM\EntityManagerInterface;
use Patreon\API;
use Patreon\OAuth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\String\ByteString;

#[Route("/creator")]
class CreatorController extends AbstractController
{

    #[Route("/index", name: 'creator_index')]
    public function index()
    {
        $user = $this->getUser();
        return $this->render('user/creator.html.twig', ['user' => $user]);
    }
}
