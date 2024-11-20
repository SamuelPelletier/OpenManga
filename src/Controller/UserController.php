<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\ChangePasswordFormType;
use App\Form\DeleteUserFormType;
use App\Form\EditUserFormType;
use App\Form\ResetPasswordRequestFormType;
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Gordalina\MixpanelBundle\Annotation as Mixpanel;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index")
     * @Mixpanel\Track("user_index")
     */
    public function index(UserRepository $userRepository, UserService $userService, EntityManagerInterface $entityManager, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }
        $rank = $userRepository->getRank($user);
        $user->setPoints($userService->calculationUserPoints($user));
        $entityManager->persist($user);
        $entityManager->flush();
        $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $_ENV['PATREON_CLIENT_ID'] . '&redirect_uri=' . $request->getSchemeAndHttpHost() . "/en/patreon_login";
        return $this->render('user/index.html.twig', ['user' => $user, 'rank' => $rank, 'patreonUrl' => $href]);
    }

    /**
     * @Route("/edit", name="user_edit")
     */
    public function edit(Request $request, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
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

    /**
     * @Route("/password", name="change_password")
     */
    public function changePassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
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

    /**
     * @Route("/delete", name="user_delete")
     */
    public function delete(Request $request, SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }
        $defaultData = ['message' => ''];
        $form = $this->createFormBuilder($defaultData)
            ->add('check', CheckboxType::class, ['data' => true, 'required' => false])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->get('security.token_storage')->setToken(null);
            $entityManager->remove($user);
            $entityManager->flush();
            $session->invalidate(0);

            return $this->redirect($this->generateUrl('index'));
        }

        return $this->render('user/delete.html.twig', [
            'deleteForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/help", name="user_help")
     */
    public function help()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }
        return $this->render('user/help.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/sync_patreon", name="user_sync_patreon")
     */
    public function syncPatreon(EntityManagerInterface $entityManager, PatreonService $patreonService)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }
        if ([$nextChargeDate, $tier] = $patreonService->getPatreonMembership($user)) {
            $user->setPatreonNextCharge($nextChargeDate);
            // todo change when new tier coming
            $user->setPatreonTier(1);
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->json(['response' => true]);
    }

    /**
     * @Route("/pay", name="pay")
     */
    public function pay(EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }

        return $this->render('pay.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/pay_proceed", name="pay_proceed")
     */
    public function payProceed(EntityManagerInterface $entityManager, Request $request, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('index');
        }

        $currency = 'EUR';
        $amount = 10.50;

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
                $payment->setSquareId($squarePayment->getId());
                $payment->setAmount($amount);
                $payment->setCurrency($currency);
                $payment->setCreatedAt(new \DateTime($squarePayment->getCreatedAt()));
                $payment->setUser($user);
                $entityManager->persist($payment);
                $entityManager->flush();
                $success = $squarePayment->getStatus() === "COMPLETED";
            } catch (\Throwable $e) {
                $this->logger->error('Payment failed because : ' . $e->getMessage());
                $success = false;
                $details = $translator->trans('square.error.internal');
            }
        } else {
            $error = $api_response->getErrors();
            $translationKey = 'square.error.' . strtolower($error[0]->getCode());
            $details = $translator->trans($translationKey);
            if ($details === $translationKey) {
                $details = $translator->trans('square.error.unknown');
            }
        }

        return $this->json(['success' => $success, 'message' => $success ? $translator->trans('square.result.success') : $translator->trans('square.result.error'), 'details' => $details]);
    }
}
