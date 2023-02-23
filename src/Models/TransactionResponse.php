<?php

namespace Glocurrency\PolarisBank\Models;

use BrokeYourBike\DataTransferObject\JsonResponse;
use Spatie\DataTransferObject\Attributes\MapFrom;

/**
 * Represents a transaction response from the PolarisBank API
 */
class TransactionResponse extends JsonResponse
{
    public ?string $message;
    public ?bool $success;

    #[MapFrom('data.provider_response_code')]
    public ?string $transactionProviderResponseCode;

    #[MapFrom('data.errors')]
    public ?string $transactionErrors;

    #[MapFrom('data.error')]
    public ?string $transactionError;

    #[MapFrom('data.provider_response.reference')]
    public ?string $reference;

    #[MapFrom('data.provider_response.payment_id')]
    public ?string $paymentId;

    #[MapFrom('data.provider_response')]
    public ?string $transactionProviderResponse;
}