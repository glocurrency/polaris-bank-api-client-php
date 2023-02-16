<?php
namespace Glocurrency\PolarisBank\Tests;
use Glocurrency\PolarisBank\Interfaces\ConfigInterface;
use Glocurrency\PolarisBank\Client;
use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;

class GetAuthTokenTest extends TestCase
{
    public function it_can_cache_and_return_auth_token()
    {
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "expires_in": "3600",
                "ext_expires_in": "3600",
                "access_token": "' . $this->tokenValue . '"
            }');

        /** @var \GuzzleHttp\Client $mockedClient */
        $mockedClient = $this->createPartialMock(\GuzzleHttp\Client::class, ['request']);
        $mockedClient->method('request')->willReturn($mockedResponse);

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache = \Mockery::spy(CacheInterface::class);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);
        $requestResult = $api->getAuthToken();

        $this->assertSame($this->tokenValue, $requestResult);

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache->shouldHaveReceived('set')
            ->once()
            ->with(
                $api->authTokenCacheKey(),
                $this->tokenValue,
                3600 - 60
            );
    }

    /** @test */
    public function it_can_return_cached_value()
    {
        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();

        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldNotReceive('request');

        /** @var \Mockery\MockInterface $mockedCache */
        $mockedCache = \Mockery::mock(CacheInterface::class);
        $mockedCache->shouldReceive('has')
            ->once()
            ->andReturn(true);
        $mockedCache->shouldReceive('get')
            ->once()
            ->andReturn($this->tokenValue);

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);
        $requestResult = $api->getAuthToken();

        $this->assertSame($this->tokenValue, $requestResult);
    }
}