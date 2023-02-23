<?php

use Glocurrency\PolarisBank\Models\FetchBankAccountNameResponse;
use PHPUnit\Framework\TestCase;

class FetchBankAccountNameResponseTest extends TestCase
{
    public function testFetchBankAccountNameResponseSuccess()
    {
        $responseArray = [
            'status' => 'Successful',
            'data' => [
                'provider_response' => [
                    'accounts' => [
                        [
                            'account_name' => 'John Doe',
                            'bank_name' => 'Polaris Bank',
                            'bank_code' => '076'
                        ]
                    ]
                ]
            ]
        ];

        $response = new FetchBankAccountNameResponse($responseArray);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals('John Doe', $response->getAccountName());
        $this->assertEquals('Polaris Bank', $response->getBankName());
        $this->assertEquals('076', $response->getBankCode());
        $this->assertEquals(json_encode($responseArray), $response->getBody());
    }

    public function testFetchBankAccountNameResponseFailure()
    {
        $responseArray = [
            'status' => 'Failed',
            'message' => 'Invalid credentials'
        ];

        $response = new FetchBankAccountNameResponse($responseArray);

        $this->assertFalse($response->isSuccess());
        $this->assertNull($response->getAccountName());
        $this->assertNull($response->getBankName());
        $this->assertNull($response->getBankCode());
        $this->assertEquals(json_encode($responseArray), $response->getBody());
    }
}