<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PatreonService;
use Doctrine\ORM\EntityManagerInterface;
use Google_Client;
use Patreon\API;
use Patreon\OAuth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\String\ByteString;

class GoogleController extends AbstractController
{

    #[Route("/google_login", name: 'google_login')]
    public function login(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserAuthenticatorInterface $userAuthenticator, AuthenticatorInterface $authenticator)
    {
        $client = new Google_Client(['client_id' => '987590718680-2gcv95f8epo9t06478hpp3t37bjjg2jd.apps.googleusercontent.com']);  // Specify the WEB_CLIENT_ID of the app that accesses the backend
        $payload = $client->verifyIdToken($request->get('credential'));
        if ($payload) {
            $userid = $payload['sub'];
            if ($user = $userRepository->findOneBy(['googleId' => $userid])) {
                $userAuthenticator->authenticateUser($user, $authenticator, $request);
            } else {
                $user = new User();
                $user->setGoogleId($userid);
                $user->setUsername($payload['name'] . '_' . $userid);
                $user->setPublicName('NewUser' . rand(1, 10000));
                $user->setPassword(ByteString::fromRandom(32)->toString());
                $entityManager->persist($user);
                $entityManager->flush();
                $user->setPublicName('NewUser' . $user->getId());
                $entityManager->flush();
                $userAuthenticator->authenticateUser($user, $authenticator, $request);
            }
            return $this->redirectToRoute('user_index');
        }
        return $this->redirectToRoute('app_login');
    }
}
