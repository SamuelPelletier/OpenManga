<?php

namespace App\Service;

use Predis\ClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisService
{
    public ClientInterface $client;

    public function __construct()
    {
        $this->client = RedisAdapter::createConnection(
            'redis://localhost',
            [
                'class' => '\Predis\Client',
                'timeout' => 10,
                'lazy' => true
            ]
        );
    }
}
