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
use Glocurrency\PolarisBank\Interfaces\BankTransactionInterface;
use Glocurrency\PolarisBank\Factories\HttpClientFactory;
use GuzzleHttp\Client as GuzzleClient;
use Glocurrency\PolarisBank\Models\FetchAuthTokenResponse;
use Glocurrency\PolarisBank\Models\TransactionResponse;

class Client implements HttpClientInterface
{
    use HttpClientTrait, HasSourceModelTrait, ResolveUriTrait;

    protected $config;
    protected $client;
    protected $cache;

    private int $ttlMarginInSeconds = 60;

    public function __construct(ConfigInterface $config, ClientInterface $client, CacheInterface $cache = null)
    {
        $this->config = $config;
        $this->client = $client;

        if ($cache) {
            $this->setCache($cache);
        }
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function authTokenCacheKey(): string
    {
        return get_class($this) . ':authToken:';
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

    public function FetchAuthTokenRaw(): FetchAuthTokenResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                'auth_provider' => 'auth_provider',
                'secure' => 'secure',
                'client_id' => $this->config->getApiKey(),
                'client_secret' => $this->config->getClientSecret(),
            ],
        ];

        $response = $this->client->request(
            HttpMethodEnum::POST->value,
            $this->config->getApiBaseUrl(),
            $options
        );

        return new FetchAuthTokenResponse($response);
    }

    public function getAuthToken()
    {

        if ($this->cache->has($this->authTokenCacheKey())) {
            $cachedToken = $this->cache->get($this->authTokenCacheKey());
            if (is_string($cachedToken)) {
                return $cachedToken;
            }
        }

        $response = $this->fetchAuthTokenRaw();

        $this->cache->set(
            $this->authTokenCacheKey(),
            $response->accessToken,
            (int) $response->expiresIn - $this->ttlMarginInSeconds
        );

        return $response->accessToken;
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

    public function setHttpClientFactory(HttpClientFactory $httpClientFactory)
    {
        $this->httpClientFactory = $httpClientFactory;
    }

    public function fetchAccountBalance(string $accountNumber): FetchAccountBalanceResponse
    {
        $uri = $this->config->getApiBaseUrl() . '/v2/transact';

        $request = $this->requestFactory->createRequest('GET', $uri)
            ->withHeader('Authorization', 'Bearer ' . $this->config->getApiKey())
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withQueryParams(['account_number' => $accountNumber]);

        $response = $this->httpClient->sendRequest($request);
        $responseData = json_decode($response->getBody()->getContents(), false);

        return new FetchAccountBalanceResponse(
            $responseData->status,
            $responseData->message,
            $responseData->data
        );
    }

    public function sendDomesticTransaction(BankTransactionInterface $bankTransaction): TransactionResponse
    {
        if($bankTransaction instanceof SourceModelInterface){
            $this->setSourceModel($bankTransaction);
        }

        $response = $this->performRequest(HttpMethodEnum::POST, 'bankAccountFT', [
            'amount' => $bankTransaction->getAmount(),
            'destination_account' => $bankTransaction->getDestinationAccount(),
            'destination_bank_code' => $bankTransaction->getDestinationBankCode(),
            'request_ref' => $bankTransaction->getRequestRef(),
            'transaction_ref' => $bankTransaction->getTransactionRef(),
            'description' => $bankTransaction->getTransactionDesc(),
        ]);

        // $responseBody = json_decode($response->getBody(), true);

        return new TransactionResponse($response);
    }

     /**
     * @param HttpMethodEnum $method
     * @param string $uri
     * @param array<mixed> $data
     * @return ResponseInterface
     */
    private function performRequest(HttpMethodEnum $method, string $uri, array $data): ResponseInterface
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config->getClientSecret(),
                'Ocp-Apim-Subscription-Key' => $this->config->getSignature()
            ],
            \GuzzleHttp\RequestOptions::JSON => $data,
        ];

        if ($this->getSourceModel()) {
            $options[\BrokeYourBike\HasSourceModel\Enums\RequestOptions::SOURCE_MODEL] = $this->getSourceModel();
        }

        $uri = (string) $this->resolveUriFor($this->config->getApiBaseUrl(), $uri);
        return $this->client->request($method->value, $uri, $options);
    }
}