<?php

namespace App\EventSubscriber;

use App\Utils\StringHelper;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RequestSubscriber implements EventSubscriberInterface
{
    protected $router;
    protected $logger;
    protected $entityManager;
    protected $tokenStorage;
    protected $authorizationChecker;

    public function __construct(
        UrlGeneratorInterface         $router,
        LoggerInterface               $logger,
        EntityManagerInterface        $entityManager,
        TokenStorageInterface         $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    )
    {
        $this->router = $router;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest'
        ];
    }

    #[NoReturn] public function onKernelRequest(RequestEvent $event): void
    {
        if (strstr($event->getRequest()->getPathInfo(), "disclaimer")) {
            return;
        }

        $userTimeSpent = $event->getRequest()->getSession()->get('user_time_spent');
        if ($this->tokenStorage->getToken() != null) {
            $user = $this->tokenStorage->getToken()->getUser();
            if ($userTimeSpent == null) {
                $event->getRequest()->getSession()->set('user_time_spent', time());
            } else {
                // Update the database every 10 seconds min
                if (time() > $userTimeSpent + 10 && $user != null && $this->authorizationChecker->isGranted('ROLE_USER')) {
                    $user->setTimeSpent($user->getTimeSpent() + (time() - $userTimeSpent));
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    // Put a new timestamp
                    $event->getRequest()->getSession()->set('user_time_spent', time());
                }
            }
        }

        $botAllow = ['google', 'yandex.com/bots', 'bingbot', 'msnbot', 'slurp', 'baidu', 'sogou', 'applebot'];

        // Check disclaimer and check googlebot agent
        if ($event->getRequest()->query->get('disclaimer') == 1 || StringHelper::stringsContains($botAllow, strtolower($event->getRequest()->headers->get('User-Agent')))) {
            $event->getRequest()->getSession()->set('disclaimer', true);
        } elseif ($event->getRequest()->getSession()->get('disclaimer') != true && $_ENV['USE_DISCLAIMER'] == 1) {
            $event->getRequest()->getSession()->set('final_url', $event->getRequest()->getUri());
            $event->setResponse(new RedirectResponse($this->router->generate('disclaimer')));
        }
    }
}
