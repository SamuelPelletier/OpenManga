<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\Payment;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\DeleteUserFormType;
use App\Form\EditUserFormType;
use App\Repository\MangaRepository;
use App\Repository\UserRepository;
use App\Service\PatreonService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Square\Authentication\BearerAuthCredentialsBuilder;
use Square\Environment;
use Square\Models\CreatePaymentRequest;
use Square\Models\Money;
use Square\SquareClientBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/user")]
class UserController extends AbstractController
{
    #[Route("/", name: 'user_index')]
    public function index(UserRepository $userRepository, UserService $userService, EntityManagerInterface $entityManager, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $rank = $userRepository->getRank($user);
        $user->setPoints($userService->calculationUserPoints($user));
        $entityManager->persist($user);
        $entityManager->flush();
        $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $_ENV['PATREON_CLIENT_ID'] . '&redirect_uri=' . $request->getSchemeAndHttpHost() . "/en/patreon_login";
        return $this->render('user/index.html.twig', ['user' => $user, 'rank' => $rank, 'patreonUrl' => $href]);
    }

    #[Route("/edit", name: 'user_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(EditUserFormType::class, $user);
        $form->handleRequest($request);

        $editionSave = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $editionSave = true;
        }

        return $this->render('user/edit.html.twig', [
            'editForm' => $form->createView(),
            'editionSave' => $editionSave
        ]);
    }

    #[Route("/password", name: 'change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(ChangePasswordFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->render('user/change_password.html.twig', [
            'passwordForm' => $form->createView()
        ]);
    }

    #[Route("/delete", name: 'user_delete')]
    public function delete(Request $request, SessionInterface $session, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage,)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $defaultData = ['message' => ''];
        $form = $this->createFormBuilder($defaultData)
            ->add('check', CheckboxType::class, ['data' => true, 'required' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tokenStorage->setToken(null);
            $entityManager->remove($user);
            $entityManager->flush();
            $session->invalidate(0);

            return $this->redirect($this->generateUrl('index'));
        }

        return $this->render('user/delete.html.twig', [
            'deleteForm' => $form->createView()
        ]);
    }

    #[Route("/help", name: 'user_help')]
    public function help()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('user/help.html.twig', ['user' => $user]);
    }

    #[Route("/sync_patreon", name: 'user_sync_patreon')]
    public function syncPatreon(PatreonService $patreonService)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }
        $patreonService->updateUserFromPatreon($user);
        return $this->json(['response' => true]);
    }

    #[Route("/pay", name: 'user_pay')]
    public function pay()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/pay.html.twig', ['user' => $user, 'square_application_id' => $_ENV['SQUARE_APPLICATION_ID'], 'square_location_id' => $_ENV['SQUARE_LOCATION_ID']]);
    }

    #[Route("/pay_proceed", name: 'pay_proceed')]
    public function payProceed(EntityManagerInterface $entityManager, Request $request, TranslatorInterface $translator, LoggerInterface $logger)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $currency = 'EUR';
        $amount = 300;

        $amount_money = new Money();
        $amount_money->setAmount($amount);
        $amount_money->setCurrency($currency);

        $body = new CreatePaymentRequest($request->toArray()['source_id'], uniqid());
        $body->setAmountMoney($amount_money);

        $client = SquareClientBuilder::init()
            ->bearerAuthCredentials(BearerAuthCredentialsBuilder::init($_ENV['SQUARE_ACCESS_TOKEN']))
            ->environment($_ENV['APP_ENV'] === 'dev' ? Environment::SANDBOX : Environment::PRODUCTION)
            ->build();

        $api_response = $client->getPaymentsApi()->createPayment($body);

        $details = null;
        if ($api_response->isSuccess()) {
            try {
                /** @var \Square\Models\Payment $squarePayment */
                $squarePayment = $api_response->getResult()->getPayment();
                $payment = new Payment();
                $payment->setUuid($squarePayment->getId());
                $payment->setAmount($amount);
                $payment->setCurrency($currency);
                $payment->setCreatedAt(new \DateTime($squarePayment->getCreatedAt()));
                $payment->setTarget('subscribe');
                $payment->setUser($user);
                $entityManager->persist($payment);
                $success = $squarePayment->getStatus() === "COMPLETED";

                $user->setPatreonTier(1);
                $now = new \DateTime('now');
                if ($user->getPatreonNextCharge()?->getTimestamp() >= $now->getTimestamp()) {
                    $date = (new \DateTime())->setTimestamp($user->getPatreonNextCharge()->getTimestamp())->modify('+1 month');
                    $user->setPatreonNextCharge($date);
                } else {
                    $user->setPatreonNextCharge($now->modify('+1 month'));
                }
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Throwable $e) {
                $logger->error('Payment failed because : ' . $e->getMessage());
                $success = false;
                $details = $translator->trans('square.error.internal');
            }
        } else {
            $success = false;
            $error = $api_response->getErrors();
            $translationKey = 'square.error.' . strtolower($error[0]->getCode());
            $details = $translator->trans($translationKey);
            if ($details === $translationKey) {
                $details = $translator->trans('square.error.unknown');
            }
        }

        return $this->json(['success' => $success, 'message' => $success ? $translator->trans('square.result.success') : $translator->trans('square.result.error'), 'details' => $details]);
    }

    #[Route("/subscribe_proceed", name: 'subscribe_proceed')]
    public function subscribeProceed(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $details = null;
        if ($user->getCredits() >= 300) {
            $now = new \DateTime('now');

            $payment = new Payment();
            $payment->setUuid(uniqid());
            $payment->setAmount(300);
            $payment->setCurrency('credit');
            $payment->setCreatedAt($now);
            $payment->setTarget('subscribe');
            $payment->setUser($user);
            $entityManager->persist($payment);

            $user->setPatreonTier(1);
            if ($user->getPatreonNextCharge()?->getTimestamp() >= $now->getTimestamp()) {
                $date = (new \DateTime())->setTimestamp($user->getPatreonNextCharge()->getTimestamp())->modify('+1 month');
                $user->setPatreonNextCharge($date);
            } else {
                $user->setPatreonNextCharge($now->modify('+1 month'));
            }
            $user->setCredits($user->getCredits() - 300);
            $entityManager->persist($user);
            $entityManager->flush();
            $success = true;
        } else {
            $success = false;
            $details = $translator->trans('square.error.amount');
        }

        return $this->json(['success' => $success, 'message' => $success ? $translator->trans('square.result.success') : $translator->trans('square.result.error'), 'details' => $details]);
    }

    #[Route("/credit", name: 'user_credit')]
    public function credit()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/credit.html.twig', ['user' => $user, 'square_application_id' => $_ENV['SQUARE_APPLICATION_ID'], 'square_location_id' => $_ENV['SQUARE_LOCATION_ID']]);
    }

    #[Route("/credit_proceed", name: 'credit_proceed')]
    public function creditProceed(EntityManagerInterface $entityManager, Request $request, TranslatorInterface $translator, LoggerInterface $logger)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }

        $amount = $request->toArray()['amount'];
        if (in_array($amount, [500, 1000, 1500, 2000, 5000])) {
            $currency = 'EUR';

            $amount_money = new Money();
            $amount_money->setAmount($amount);
            $amount_money->setCurrency($currency);

            $body = new CreatePaymentRequest($request->toArray()['source_id'], uniqid());
            $body->setAmountMoney($amount_money);

            $client = SquareClientBuilder::init()
                ->bearerAuthCredentials(BearerAuthCredentialsBuilder::init($_ENV['SQUARE_ACCESS_TOKEN']))
                ->environment($_ENV['APP_ENV'] === 'dev' ? Environment::SANDBOX : Environment::PRODUCTION)
                ->build();

            $api_response = $client->getPaymentsApi()->createPayment($body);

            $details = null;
            if ($api_response->isSuccess()) {
                try {
                    /** @var \Square\Models\Payment $squarePayment */
                    $squarePayment = $api_response->getResult()->getPayment();
                    $payment = new Payment();
                    $payment->setUuid($squarePayment->getId());
                    $payment->setAmount($amount);
                    $payment->setCurrency($currency);
                    $payment->setCreatedAt(new \DateTime($squarePayment->getCreatedAt()));
                    $payment->setTarget('credit');
                    $payment->setUser($user);
                    $entityManager->persist($payment);
                    $success = $squarePayment->getStatus() === "COMPLETED";

                    $user->setCredits($user->getCredits() + $amount);
                    $entityManager->persist($user);
                    $entityManager->flush();
                } catch (\Throwable $e) {
                    $logger->error('Payment failed because : ' . $e->getMessage());
                    $success = false;
                    $details = $translator->trans('square.error.internal');
                }
            } else {
                $success = false;
                $error = $api_response->getErrors();
                $translationKey = 'square.error.' . strtolower($error[0]->getCode());
                $details = $translator->trans($translationKey);
                if ($details === $translationKey) {
                    $details = $translator->trans('square.error.unknown');
                }
            }
        } else {
            $success = false;
            $details = $translator->trans('square.error.amount');
        }

        return $this->json(['success' => $success, 'message' => $success ? $translator->trans('square.result.success') : $translator->trans('square.result.error'), 'details' => $details]);
    }

    #[Route("/life_product", name: 'user_life_product')]
    public function lifeProduct()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/life_product.html.twig', ['user' => $user]);
    }

    #[Route("/life_product_proceed", name: 'life_product_proceed')]
    public function lifeProductProceed(EntityManagerInterface $entityManager, Request $request, TranslatorInterface $translator, LoggerInterface $logger)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $details = null;
        if ($user->getCredits() >= 1500) {
            $now = new \DateTime('now');

            $payment = new Payment();
            $payment->setUuid(uniqid());
            $payment->setAmount(1500);
            $payment->setCurrency('credit');
            $payment->setCreatedAt($now);
            $payment->setTarget('life_product');
            $payment->setUser($user);
            $entityManager->persist($payment);

            $user->setIsUnlockOldManga(true);
            $user->setCredits($user->getCredits() - 1500);
            $entityManager->persist($user);
            $entityManager->flush();
            $success = true;
        } else {
            $success = false;
            $details = $translator->trans('square.error.amount');
        }

        return $this->json(['success' => $success, 'message' => $success ? $translator->trans('square.result.success') : $translator->trans('square.result.error'), 'details' => $details]);
    }

    #[Route("/invoice", name: 'user_invoice')]
    public function invoice()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/invoice.html.twig', ['user' => $user]);
    }

    #[Route("/manga/favorite", defaults: ['page' => 1], methods: ['GET'], name: 'index_favorite')]
    #[Route("/manga/favorite/page/{page<[1-9]\d*>}", methods: ['GET'], name: 'index_favorite_paginated')]
    #[Cache(maxage: 10)]
    public function mangaFavorite(int $page): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }

        $filterFunction = function (Manga $manga) {
            return !$manga->isCorrupted();
        };

        return $this->render('user/manga_favorite.html.twig', ['mangas' => $user->getFavoriteMangas()->filter($filterFunction)->slice(($page - 1) * Manga::NUM_ITEMS, Manga::NUM_ITEMS), 'total' => $user->getFavoriteMangas()->filter($filterFunction)->count()]);
    }
}
