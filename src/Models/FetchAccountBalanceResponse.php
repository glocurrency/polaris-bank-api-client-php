<?php

namespace Glocurrency\PolarisBank\Models;

use BrokeYourBike\DataTransferObject\JsonResponse;

class FetchAccountBalanceResponse extends JsonResponse
{
    public string $status;
    public string $message;
    public object $data;

    public function __construct(string $status, string $message, object $data)
    {
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json);
        return new self(
            $decoded->status,
            $decoded->message,
            $decoded->data
        );
    }
}