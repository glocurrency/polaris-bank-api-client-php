<?php

namespace Glocurrency\PolarisBank\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Glocurrency\PolarisBank\Models\TransactionResponse;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Glocurrency\PolarisBank\Client;

class FetchDomesticTransactionStatusRawTest extends TestCase
{
    private string $appId = 'api-key';
    private string $clientSecret = 'client_secret';
    private string $signature = 'signature';
    /** @test */
    public function it_can_fetch_domestic_transaction_status_raw(): void 
    {
        $paymentId = '136FTTP200620003';
        $reference = 'E9093F855F01461298E89CD043CEDB3C';
        
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getApiBaseUrl')->willReturn('https://api.example/');
        $mockedConfig->method('getAppId')->willReturn($this->appId);
        $mockedConfig->method('getClientSecret')->willReturn($this->clientSecret);
        $mockedConfig->method('getSignature')->willReturn($this->signature);

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "status": "Successful",
                "message": "Transaction processed successfully",
                "data": {
                "provider_response_code": "00",
                "provider": "Polaris",
                "errors": null,
                "error": null,
                "provider_response": {
                    "destination_institution_code": "076",
                    "transaction_final_amount": 1000,
                    "reference": "E9093F855F01461298E89CD043CEDB3C",
                    "payment_id": "136FTTP200620003"
                }
                }
            }');

        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class)->makePartial();
        $mockedClient->shouldReceive('request')->withArgs([
            'POST',
            'https://api.example/getBankFTStatus',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Ocp-Apim-Subscription-Key' => $this->signature,
                ],
                \GuzzleHttp\RequestOptions::JSON => [
                    'paymentId' => $paymentId,
                    'reference' => $reference,
                    'appId' => $this->apiKey,
                ],
            ],
        ])->once()->andReturn($mockedResponse);

        $mockedClient->shouldReceive('close')->once();

        $mockedCache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $mockedCache->method('has')->willReturn(true);
        $mockedCache->method('get')->willReturn([]);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);

        $requestResult = $api->fetchDomesticTransactionStatusRaw($apiKey, $reference);

        $this->assertInstanceOf(TransactionResponse::class, $requestResult);
        $this->assertEquals('Successful', $requestResult->status);
        $this->assertEquals('Transaction processed successfully', $requestResult->message);
        $this->assertEquals('00', $requestResult->provider_response_code);
        $this->assertEquals('Polaris', $requestResult->provider);
        $this->assertNull($requestResult->errors);
        $this->assertNull($requestResult->error);
        $this->assertEquals('076', $requestResult->destination_institution_code);
        $this->assertEquals(1000, $requestResult->transaction_final_amount);
        $this->assertEquals($reference, $requestResult->reference);
        $this->assertEquals('076', $requestResult->provider_response_code);
        $this->assertEquals(1000, $requestResult->transaction_final_amount);
        $this->assertEquals('E9093F855F01461298E89CD043CEDB3C', $requestResult->reference);
        $this->assertEquals('136FTTP200620003', $requestResult->payment_id);
    }

}