<?php

namespace Glocurrency\PolarisBank;

use BrokeYourBike\HttpEnums\HttpMethodEnum;
use BrokeYourBike\HttpClient\HttpClientInterface;
use BrokeYourBike\HttpClient\HttpClientTrait;
use BrokeYourBike\HasSourceModel\HasSourceModelTrait;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\ResolveUri\ResolveUriTrait;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Glocurrency\PolarisBank\Models\FetchAccountBalanceResponse;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class Client implements HttpClientInterface
{
    use HttpClientTrait, HasSourceModelTrait, ResolveUriTrait;

    protected $config;
    protected $client;
    protected $cache;

    public function __construct(ConfigInterface $config, ClientInterface $client, CacheInterface $cache = null)
    {
        $this->config = $config;
        $this->client = $client;

        if ($cache) {
            $this->setCache($cache);
        }
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function sendRequest($method, $uri, $data = null, $headers = [])
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->config->getApiKey(),
            'Signature' => $this->generateSignature(),
        ], $headers);

        $response = $this->httpClientSendRequest($method, $uri, $data, $headers);

        return $this->handleResponse($response);
    }

    public function generateSignature()
    {
        $requestRef = $this->getSourceModel() instanceof SourceModelInterface
            ? $this->getSourceModel()->getRequestRef()
            : uniqid();

        $data = $requestRef . $this->config->getClientSecret();

        return md5($data);
    }

    public function getAuthToken()
    {
        $cacheKey = 'polaris_access_token';
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $authData = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config->getApiKey(),
            'client_secret' => $this->config->getClientSecret(),
            'scope' => '',
        ];

        $response = $this->httpClient->request('POST', '/auth/token', [
            'form_params' => $authData,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);
        $authToken = $responseBody['access_token'];

        $this->cache->set($cacheKey, $authToken, $responseBody['expires_in'] - 60);

        return $authToken;
    }

    protected function handleResponse(ResponseInterface $response)
    {
        $responseBody = (string) $response->getBody();
        $responseData = json_decode($responseBody, true);

        if ($response->getStatusCode() >= 400) {
            $message = isset($responseData['message']) ? $responseData['message'] : 'Unknown error';
            throw new \Exception($message, $response->getStatusCode());
        }

        return $responseData;
    }

    public function fetchAccountBalance($accountNumber)
    {
        $uri = $this->resolveUri("/accounts/balance/$accountNumber");

        $response = $this->sendRequest(HttpMethodEnum::GET, $uri);

        return new FetchAccountBalanceResponse($response);
    }
}