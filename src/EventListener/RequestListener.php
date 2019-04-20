<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RequestListener
{
    protected $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (strstr($event->getRequest()->getPathInfo(), "disclaimer")) {
            return;
        }

        // Check disclaimer and check googlebot agent
        if ($event->getRequest()->query->get('disclaimer') == 1 || strpos($event->getRequest()->headers->get('User-Agent'),
                "www.google.com/bot.html") != false) {
            $event->getRequest()->getSession()->set('disclaimer', true);
        } else {
            if ($event->getRequest()->getSession()->get('disclaimer') != true && $_ENV['USE_DISCLAIMER'] == 1) {
                $event->setResponse(new RedirectResponse($this->router->generate('disclaimer')));
            }
        }


    }
}