<?php


namespace Glocurrency\PolarisBank\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Glocurrency\PolarisBank\Models\TransactionResponse;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Glocurrency\PolarisBank\Interfaces\BankTransactionInterface;
use Glocurrency\PolarisBank\Client;

class FetchDomesticTransactionStatusRawTest extends TestCase
{
    private string $apiKey = 'api-key';
    private string $clientSecret = 'secure-token';
    private string $signature = 'signature';

    /** @test */
    public function it_can_prepare_request(): void
    {
        /** @var BankTransactionInterface $transaction */
        $transaction = $this->getMockBuilder(BankTransactionInterface::class)->getMock();

        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getApiBaseUrl')->willReturn('https://api.example/');
        $mockedConfig->method('getApiKey')->willReturn($this->apiKey);
        $mockedConfig->method('getClientSecret')->willReturn($this->clientSecret);
        $mockedConfig->method('getSignature')->willReturn($this->signature);

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
            'https://api.example/bankAccountFT',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->clientSecret}",
                    'Ocp-Apim-Subscription-Key' => $this->signature,
                ],
                \GuzzleHttp\RequestOptions::JSON => [
                    'amount' => $transaction->getAmount(),
                    'destination_account' => $transaction->getDestinationAccount(),
                    'destination_bank_code' => $transaction->getDestinationBankCode(),
                    'request_ref' => $transaction->getRequestRef(),
                    'transaction_ref' => $transaction->getTransactionRef(),
                    'description' => $transaction->getTransactionDesc(),
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

        $requestResult = $api->sendDomesticTransaction($transaction);

        $this->assertInstanceOf(TransactionResponse::class, $requestResult);
        $this->assertSame(null, $requestResult->transactionErrors);
    }

}