<?php

namespace Glocurrency\PolarisBank\Factories;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class HttpClientFactory
{
    public function createHttpClient(array $config): ClientInterface
    {
        return new Client($config);
    }
}