<?php

namespace Glocurrency\PolarisBank\Tests;

use Glocurrency\PolarisBank\Client;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Glocurrency\PolarisBank\Models\TransactionResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\ClientInterface;

class FetchDomesticTransactionStatusRawTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $cache = $this->createMock(CacheInterface::class);

        $client = $this->createMock(\GuzzleHttp\ClientInterface::class);

        $this->client = new Client($config, $client);
    }

    /**
     * @dataProvider responseProvider
     */
    public function testFetchDomesticTransactionStatusRaw(string $status, string $message, string $transactionRef, string $requestRef, float $amount, string $recipientAccountNumber, string $senderAccountNumber, string $description, string $expectedStatus)
    {
        $responseData = [
            'status' => $status,
            'message' => $message,
            'data' => [
                'transaction_reference' => $transactionRef,
                'request_reference' => $requestRef,
                'amount' => $amount,
                'destination_account_number' => $recipientAccountNumber,
                'sender_account_number' => $senderAccountNumber,
                'description' => $description,
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn(json_encode($responseData));

        $transactionResponse = new TransactionResponse($responseData);

        $this->assertEquals($expectedStatus, $transactionResponse->status);
    }

    public static function responseProvider()
    {
        return [
            ['success', 'Transaction successful', 'ABC123', '123456', 1000.0, '1234567890', '0987654321', 'Payment for goods and services', 'success'],
            ['error', 'Invalid account number', 'XYZ987', '654321', 500.0, '1234567890', '0987654321', 'Payment for goods and services', 'error'],
        ];
    }
}