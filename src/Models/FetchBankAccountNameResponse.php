<?php

namespace Glocurrency\PolarisBank\Models;

use BrokeYourBike\DataTransferObject\JsonResponse;
use Spatie\DataTransferObject\Attributes\MapFrom;

class FetchBankAccountNameResponse
{
    protected $success;
    protected $accountName;
    protected $bankName;
    protected $bankCode;
    protected $response;
    protected $getContents;

    public function __construct(array $response)
    {
        $this->success = isset($response['status']) && $response['status'] === 'Successful';

        if ($this->success) {
            $data = $response['data']['provider_response']['accounts'][0];
            $this->accountName = $data['account_name'];
            $this->bankName = $data['bank_name'];
            $this->bankCode = $data['bank_code'];
        }

        $this->response = json_encode($response);

    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function getBankCode(): ?string
    {
        return $this->bankCode;
    }

    public function getBody()
    {
        return $this->response;
    }
}