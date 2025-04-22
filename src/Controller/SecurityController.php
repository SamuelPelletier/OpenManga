<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    #[Route("/login", name: 'app_login')]
    #[Route("/register", name: 'app_register')]
    public function login(
        AuthenticationUtils         $authenticationUtils,
        Request                     $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface      $entityManager,
        UserAuthenticatorInterface  $userAuthenticator,
        AuthenticatorInterface      $authenticator
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('user_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setPublicName('NewUser' . rand(1, 10000));
            $entityManager->persist($user);
            $entityManager->flush();

            $user->setPublicName('NewUser' . $user->getId());
            $entityManager->flush();

            $userAuthenticator->authenticateUser($user, $authenticator, $request);

            return $this->redirectToRoute('user_index');
        }
        $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $_ENV['PATREON_CLIENT_ID'] . '&redirect_uri=' . $request->getSchemeAndHttpHost() . "/en/patreon_login";
        return $this->render('security/login.html.twig',
            ['last_username' => $lastUsername, 'error' => $error, 'registrationForm' => $form->createView(), 'patreonUrl' => $href]);
    }

    #[Route("/logout", name: 'app_logout')]
    public function logout()
    {
        return;
    }
}
