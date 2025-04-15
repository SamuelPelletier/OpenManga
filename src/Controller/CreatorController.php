<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\User;
use App\Form\MangaNewFormType;
use App\Form\MangaTranslationFormType;
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

    #[Route("/upload", name: 'creator_upload')]
    public function upload(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(MangaNewFormType::class);
        $form2 = $this->createForm(MangaTranslationFormType::class);
        if ($request->get('manga_translation_form')) {
            $form2->handleRequest($request);
            if ($form2->isSubmitted() && $form2->isValid()) {
                dd("non");
                /*// encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();
                // do anything else you need here, like send an email

                $userAuthenticator->authenticateUser($user, $authenticator, $request);

                return $this->redirectToRoute('user_index');*/
            }
        } else {
            $form->handleRequest($request);
            foreach ($request->files->all()['manga_new_form']['files'] as $file) {
               // dd($file->getMimeType());
            }
            if ($form->isSubmitted() && $form->isValid()) {
               // dd($form);
                /*// encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();
                // do anything else you need here, like send an email

                $userAuthenticator->authenticateUser($user, $authenticator, $request);

                return $this->redirectToRoute('user_index');*/
            }
        }

        return $this->render('user/creator.html.twig', ['user' => $user, 'mangaNewForm' => $form->createView(), 'mangaTranslationForm' => $form2->createView()]);
    }
}
