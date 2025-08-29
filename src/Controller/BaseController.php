<?php

namespace App\Controller;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $jsonQueryParameter = ($this->container->get('request_stack')->getCurrentRequest()->headers->get('Content-Type'));
        if ($jsonQueryParameter === 'application/json' || $this->container->get('request_stack')->getCurrentRequest()->query->getBoolean('json')) {
            if (current($parameters) instanceof Paginator) {
                $data = current($parameters)->getQuery()->getResult();
                $total = current($parameters)->count();
                $result = ['data' => $data, 'total' => $total];
            } else {
                $result = ['data' => current($parameters)];
                if (isset($parameters['total'])) {
                    $result['total'] = $parameters['total'];
                }
            }
            return $this->json($result);
        }
        return parent::render($view, $parameters, $response);
    }
}
