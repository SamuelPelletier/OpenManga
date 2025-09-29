<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PatreonService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Google_Client;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Patreon\API;
use Patreon\OAuth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\String\ByteString;

class GoogleController extends AbstractController
{
    const APP_IOS = '987590718680-q1hck96puauhl8uigdk1ake8ot1lv60s.apps.googleusercontent.com';
    const APP_ANDROID = '987590718680-r3mgjflib2bfp6tr9ut46de5o8d96kjc.apps.googleusercontent.com';
    const APP_WEB = '987590718680-2gcv95f8epo9t06478hpp3t37bjjg2jd.apps.googleusercontent.com';

    #[Route("/google_login", name: 'google_login')]
    public function login(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserAuthenticatorInterface $userAuthenticator, AuthenticatorInterface $authenticator, JWTTokenManagerInterface $JWTTokenManager, RefreshTokenManagerInterface $refreshTokenManager)
    {
        $platform = $this::{'APP_' . strtoupper($request->get('platform', 'web'))};
        $client = new Google_Client(['client_id' => $platform]);
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
            $jsonQueryParameter = ($this->container->get('request_stack')->getCurrentRequest()->headers->get('Content-Type'));
            if ($jsonQueryParameter === 'application/json' || $this->container->get('request_stack')->getCurrentRequest()->query->getBoolean('json')) {
                $token = $JWTTokenManager->create($user);
                $refreshToken = $refreshTokenManager->create();
                $refreshToken->setUsername($user->getUserIdentifier());
                $refreshToken->setRefreshToken();
                $refreshToken->setValid((new DateTime())->modify('+1 month'));

                $refreshTokenManager->save($refreshToken);
                return new JsonResponse(['token' => $token, 'refresh_token' => $token]);
            }
            return $this->redirectToRoute('user_index');
        }
        return $this->redirectToRoute('app_login');
    }
}
