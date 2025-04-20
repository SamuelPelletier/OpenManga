<?php

namespace App\EventListener;

use App\Service\PatreonService;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessListener
{
    private PatreonService $patreonService;

    public function __construct(PatreonService $patreonService)
    {
        $this->patreonService = $patreonService;
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        if (str_ends_with($event->getRequest()->getPathInfo(), "register")) {
            return;
        }

        $user = $event->getUser();
        $this->patreonService->updateUserFromPatreon($user);
    }

}
