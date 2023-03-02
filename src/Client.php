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

    /** @link https://docs.openbanking.vulte.ng/#b3f5f0aa-e4ff-4719-bc29-65230e92ea3d */
    public function sendTransaction(BankTransactionInterface $bankTransaction): TransactionResponse
    {
        if($bankTransaction instanceof SourceModelInterface){
            $this->setSourceModel($bankTransaction);
        }

        $data = [
            "request_ref" => $bankTransaction->getRequestRef(),
            "request_type" => $bankTransaction->getRequestType(),
            "auth" => [
                "type" => $bankTransaction->getAuthType(),
                "secure"=> $bankTransaction->getSecure(),
                "auth_provider" => (string) $bankTransaction->getAuthProvider(),
                "route_mode" => $bankTransaction->getRouteMode()
            ],
            "transaction" => [
                "mock_mode" => $bankTransaction->getMockMode(),
                "transaction_ref" => $bankTransaction->getTransactionRef(),
                "transaction_desc"=> (string) $bankTransaction->getTransactionDesc(),
                "transaction_ref_parent"=> $bankTransaction->getTransactionRefParent(),
                "amount" => $bankTransaction->getAmount(),
                "customer" => [
                    "customer_ref" => $bankTransaction->getCustomerRef(),
                    "firstname" => $bankTransaction->getFirstName(),
                    "surname" => $bankTransaction->getSurname(),
                    "email" => $bankTransaction->getEmail(),
                    "mobile_no" => $bankTransaction->getMobileNo()
                ],
                "meta" => (array) $bankTransaction->getMeta(),
                "details" => (array) $bankTransaction->getDetails()
            ]
        ];

        $response = $this->performRequest(HttpMethodEnum::POST, 'v1/transact', $data);
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
                'Signature' => $this->config->getSignature()
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