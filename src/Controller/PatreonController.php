<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Patreon\API;
use Patreon\OAuth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\String\ByteString;

class PatreonController extends AbstractController
{
    /**
     * @Route("/patreon_login", name="patreon_login")
     */
    public function login(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, UserAuthenticatorInterface $userAuthenticator, AuthenticatorInterface $authenticator)
    {
        if (isset($_GET['code']) && !empty($_GET['code'])) {

            $oauth_client = new OAuth($_ENV['PATREON_CLIENT_ID'], $_ENV['PATREON_CLIENT_SECRET']);

            $tokens = $oauth_client->get_tokens($_GET['code'], $request->getSchemeAndHttpHost() . "/en/patreon_login");
            $access_token = $tokens['access_token'];
            $refresh_token = $tokens['refresh_token'];
            if (isset($access_token)) {
                if ($user = $this->getUser()) {
                    $user->setPatreonAccessToken($access_token);
                    $user->setPatreonRefreshToken($refresh_token);
                    $entityManager->persist($user);
                    $entityManager->flush();
                } else {
                    if ($user = $userRepository->findOneBy(['patreonAccessToken' => $access_token])) {
                        $userAuthenticator->authenticateUser($user, $authenticator, $request);
                    } else {
                        $api_client = new API($access_token);
                        $api_client->api_return_format = 'object';
                        $patron_response = $api_client->fetch_user();
                        $user = new User();
                        $user->setPatreonAccessToken($access_token);
                        $user->setPatreonRefreshToken($refresh_token);
                        $user->setUsername($patron_response->data->attributes->full_name . '_' . $patron_response->data->id);
                        $user->setPassword(ByteString::fromRandom(32)->toString());
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $userAuthenticator->authenticateUser($user, $authenticator, $request);
                    }
                }
                return $this->redirectToRoute('user_index');
            }
        }
        return $this->redirectToRoute('app_login');
    }
}
