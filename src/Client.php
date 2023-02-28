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
use Glocurrency\PolarisBank\Models\FetchBankAccountNameResponse;
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
    protected $httpClient;
    protected $cache;
    
    private int $ttlMarginInSeconds = 60;

    public function __construct(ConfigInterface $config, ClientInterface $httpClient, CacheInterface $cache = null)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /** @link link-to-the-api-method-documentation */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /** @link link-to-the-api-method-documentation */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /** @link link-to-the-api-method-documentation */
    public function authTokenCacheKey(): string
    {
        return get_class($this) . ':authToken:';
    }

    /** @link link-to-the-api-method-documentation */
    public function generateSignature()
    {
        $requestRef = $this->getSourceModel() instanceof SourceModelInterface
            ? $this->getSourceModel()->getRequestRef()
            : uniqid();

        $data = $requestRef . $this->config->getClientSecret();

        return md5($data);
    }

    /** @link link-to-the-api-method-documentation */
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

        $response = $this->httpClient->request(
            HttpMethodEnum::POST->value,
            $this->config->getApiBaseUrl(),
            $options
        );

        return new FetchAuthTokenResponse($response);
    }

    /** @link link-to-the-api-method-documentation */
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

    /** @link link-to-the-api-method-documentation */
    public function fetchDomesticTransactionStatusRaw(BankTransactionInterface $bankTransaction): TransactionResponse
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

        return new TransactionResponse($response);
    }

    /** @link link-to-the-api-method-documentation */
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

        return new TransactionResponse($response);
    }

     /**
     * @param HttpMethodEnum $method
     * @param string $uri
     * @param array<mixed> $data
     * @return ResponseInterface
     */
    
    /** @link link-to-the-api-method-documentation */
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
        return $this->httpClient->request($method->value, $uri, $options);
    }
}