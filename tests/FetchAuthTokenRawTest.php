<?php

namespace Glocurrency\PolarisBank\Tests;

use PHPUnit\Framework\TestCase;
use Glocurrency\PolarisBank\Client;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Glocurrency\PolarisBank\Models\FetchAuthTokenResponse;

class FetchAuthTokenRawTest extends TestCase
{
    /** @test */
    public function testFetchAuthTokenResponseProperties()
    {
        $mockConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockConfig->method('getApiBaseUrl')->willReturn('https://auth.example/');
        $mockConfig->method('getApiKey')->willReturn('client-id');
        $mockConfig->method('getClientSecret')->willReturn('super-secret-value');

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "token_type": "Bearer",
                "expires_in": "3599",
                "ext_expires_in": "3599",
                "expires_on": "1625077289",
                "not_before": "1625073389",
                "resource": "12345",
                "access_token": "super-secure-token"
            }');

        /** @var \Mockery\MockInterface $mockClient */
        $mockClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockClient->shouldReceive('request')->withArgs([
            'POST',
            'https://auth.example/',
            [
                \GuzzleHttp\RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                ],
                \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                    'auth_provider' => 'auth_provider',
                    'secure' => 'secure',
                    'client_id' => 'client-id',
                    'client_secret' => 'super-secret-value',
                ],
            ],
        ])->once()->andReturn($mockedResponse);

        $mockCache = $this->getMockBuilder(CacheInterface::class)->getMock();

        /**
         * @var ConfigInterface $mockConfig
         * @var \GuzzleHttp\Client $mockClient
         * @var CacheInterface $mockCache
         * */
        $api = new Client($mockConfig, $mockClient, $mockCache);
        $requestResult = $api->fetchAuthTokenRaw();

        $this->assertInstanceOf(FetchAuthTokenResponse::class, $requestResult);
    }
}