<?php

namespace GloCurrency\PolarisBank\Models;

use Spatie\DataTransferObject\Attributes\MapFrom;
use BrokeYourBike\DataTransferObject\JsonResponse;

/**
 * Represents a transaction response from a payment provider
 */
class TransactionResponse extends JsonResponse
{
    public string $status;
    public string $message;
    public ?string $provider_response_code;
    public ?string $provider;
    public ?array $errors;
    public ?string $error;
    public ?array $provider_response;
    public ?array $client_info;

    #[MapFrom('data.provider_response.transaction_final_amount')]
    public float $amount;

    #[MapFrom('data.provider_response.beneficiary_account_name')]
    public string $beneficiaryName;

    #[MapFrom('data.provider_response.beneficiary_account_number')]
    public string $beneficiaryAccountNumber;

    #[MapFrom('data.provider_response.originator_account_number')]
    public string $originatorAccountNumber;

    #[MapFrom('data.provider_response.narration')]
    public string $narration;

    #[MapFrom('data.provider_response.reference')]
    public string $reference;

    #[MapFrom('data.provider_response.payment_id')]
    public string $paymentId;

    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->message = $data['message'];
    }
};



