<?php

namespace Glocurrency\PolarisBank\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Glocurrency\PolarisBank\Models\TransactionResponse;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Glocurrency\PolarisBank\Interfaces\BankTransactionInterface;
use Glocurrency\PolarisBank\Client;

class sendTransactionTest extends TestCase
{
    private string $apiKey = 'api-key';
    private string $clientSecret = 'secure-token';
    private string $requestRef = 'request_ref';

    /** @test */
    public function it_can_prepare_request(): void
    {
        
        /** @var BankTransactionInterface $transaction */
        $transaction = $this->getMockBuilder(BankTransactionInterface::class)->getMock();

        $mockedDisbursmentTest = $this->getMockBuilder(DisbursmentTest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getBaseUrl')->willReturn('https://api.example/');
        $mockedConfig->method('getKey')->willReturn($this->apiKey);
        $mockedConfig->method('getClientSecret')->willReturn($this->clientSecret);

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "data": {
                    "provider_response_code": "00",
                    "provider": "Polaris",
                    "provider_response": "A random transaction",
                    "errors": null,
                    "error": null,
                    "provider_response": {
                        "destination_institution_code": "076",
                        "beneficiary_account_name": "WALTER JAMES BLUNT",
                        "beneficiary_account_number": "0099888876",
                        "beneficiary_kyc_level": "",
                        "originator_account_name": "",
                        "originator_account_number": "0055666543",
                        "originator_kyc_level": "",
                        "narration": "A random transaction",
                        "transaction_final_amount": 1000,
                        "reference": "E9093F855F01461298E89CD043CEDB3C",
                        "payment_id": "136FTTP200620003"
                    },
                },
                "message": "Success - Approved or successfully processed",
                "success": "Transaction processed successfully",
            }');

        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldReceive('request')->withArgs([
            'POST',
            'https://api.example/v1/transact',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Signature' => md5($transaction->getRequestRef() . ";" . $this->clientSecret),
                ],
                \GuzzleHttp\RequestOptions::JSON => [
                    "request_ref" => $transaction->getRequestRef(),
                    "request_type" => $transaction->getRequestType(),
                    "auth" => [
                        "type" => $transaction->getAuthType(),
                        "secure"=> $transaction->getSecure(),
                        "auth_provider" => (string) $transaction->getAuthProvider(),
                        "route_mode" => $transaction->getRouteMode()
                    ],
                    "transaction" => [
                        "mock_mode" => $transaction->getMockMode(),
                        "transaction_ref" => $transaction->getTransactionRef(),
                        "transaction_desc"=> (string) $transaction->getTransactionDesc(),
                        "transaction_ref_parent"=> $transaction->getTransactionRefParent(),
                        "amount" => $transaction->getAmount(),
                        "customer" => [
                            "customer_ref" => $transaction->getCustomerRef(),
                            "firstname" => $transaction->getFirstName(),
                            "surname" => $transaction->getSurname(),
                            "email" => $transaction->getEmail(),
                            "mobile_no" => $transaction->getMobileNo()
                        ],
                        "meta" => (array) $transaction->getMeta(),
                        "details" => (array) $transaction->getDetails()
                    ]
                ],
            ],
        ])->once()->andReturn($mockedResponse);

        $mockedCache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $mockedCache->method('has')->willReturn(true);
        $mockedCache->method('get')->willReturn($this->clientSecret);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);

        $requestResult = $api->sendTransaction($transaction);

        $this->assertInstanceOf(TransactionResponse::class, $requestResult);
    }

    /** @test */
    public function it_can_handle_failed_reponse()
    {
        /** @var BankTransactionInterface $transaction */
        $transaction = $this->getMockBuilder(BankTransactionInterface::class)->getMock();

        $mockedDisbursmentTest = $this->getMockBuilder(DisbursmentTest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getBaseUrl')->willReturn('https://api.example/');
        $mockedConfig->method('getKey')->willReturn($this->apiKey);
        $mockedConfig->method('getClientSecret')->willReturn($this->clientSecret);

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(500);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "status": "Failed",
                "message": "Invalid response received from provider",
                "data": {
                    "options": null,
                    "provider_response_code": null,
                    "provider": "Polaris",
                    "errors": [
                      {
                        "code": "04",
                        "message": "invalid data"
                      }
                    ],
                    "error": {
                      "code": "04",
                      "message": "invalid data"
                    },
                    "provider_response": null,
                  }
            }');


        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldReceive('request')->withArgs([
            'POST',
            'https://api.example/v1/transact',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Signature' => md5($transaction->getRequestRef() . ";" . $this->clientSecret),
                ],
                \GuzzleHttp\RequestOptions::JSON => [
                    "request_ref" => $transaction->getRequestRef(),
                    "request_type" => $transaction->getRequestType(),
                    "auth" => [
                        "type" => $transaction->getAuthType(),
                        "secure"=> $transaction->getSecure(),
                        "auth_provider" => (string) $transaction->getAuthProvider(),
                        "route_mode" => $transaction->getRouteMode()
                    ],
                    "transaction" => [
                        "mock_mode" => $transaction->getMockMode(),
                        "transaction_ref" => $transaction->getTransactionRef(),
                        "transaction_desc"=> (string) $transaction->getTransactionDesc(),
                        "transaction_ref_parent"=> $transaction->getTransactionRefParent(),
                        "amount" => $transaction->getAmount(),
                        "customer" => [
                            "customer_ref" => $transaction->getCustomerRef(),
                            "firstname" => $transaction->getFirstName(),
                            "surname" => $transaction->getSurname(),
                            "email" => $transaction->getEmail(),
                            "mobile_no" => $transaction->getMobileNo()
                        ],
                        "meta" => (array) $transaction->getMeta(),
                        "details" => (array) $transaction->getDetails()
                    ]
                ],
            ],
        ])->once()->andReturn($mockedResponse);

        $mockedCache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $mockedCache->method('has')->willReturn(true);
        $mockedCache->method('get')->willReturn($this->clientSecret);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);

        $requestResult = $api->sendTransaction($transaction);

        $this->assertInstanceOf(TransactionResponse::class, $requestResult);
        $this->assertSame(null, $requestResult->message);
    }
}
