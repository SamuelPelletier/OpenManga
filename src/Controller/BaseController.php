<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $jsonQueryParameter = ($this->container->get('request_stack')->getCurrentRequest()->headers->get('Content-Type'));
        if ($jsonQueryParameter === 'application/json' || $this->container->get('request_stack')->getCurrentRequest()->query->getBoolean('json')) {
            return $this->json(current($parameters)->getQuery()->getArrayResult());
        }
        return parent::render($view, $parameters, $response);
    }


}
