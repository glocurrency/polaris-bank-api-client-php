<?php

namespace Glocurrency\PolarisBank\Tests;

use Glocurrency\PolarisBank\Models\FetchAccountBalanceResponse;
use PHPUnit\Framework\TestCase;

class FetchAccountBalanceRawTest extends TestCase
{
    public function testFromJson()
    {
        $json = '{"status": "success", "message": "Balance fetched successfully", "data": {"available_balance": 1000.00, "ledger_balance": 2000.00}}';

        $response = FetchAccountBalanceResponse::fromJson($json);

        $this->assertInstanceOf(FetchAccountBalanceResponse::class, $response);
        $this->assertEquals('success', $response->status);
        $this->assertEquals('Balance fetched successfully', $response->message);
        $this->assertInstanceOf(\stdClass::class, $response->data);
        $this->assertEquals(1000.00, $response->data->available_balance);
        $this->assertEquals(2000.00, $response->data->ledger_balance);
    }
}