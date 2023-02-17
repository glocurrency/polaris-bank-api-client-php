<?php

namespace Glocurrency\PolarisBank\Models;

use BrokeYourBike\DataTransferObject\JsonResponse;
use Spatie\DataTransferObject\Attributes\MapFrom;

/**
 * Represents a transaction response from the PolarisBank API
 */
class TransactionResponse extends JsonResponse
{
    /** @var string */
    public string $status;

    /** @var string */
    public string $message;

    /** @var string */
    public string $transactionRef;

    /** @var string */
    public string $requestRef;

    #[MapFrom('data.amount')]
    /** @var float */
    public float $amount;

    #[MapFrom('data.destination_account_number')]
    /** @var string */
    public string $recipientAccountNumber;

    #[MapFrom('data.sender_account_number')]
    /** @var string */
    public string $senderAccountNumber;

    #[MapFrom('data.description')]
    /** @var string */
    public string $description;

    public function __construct(array $parameters)
    {
        $this->status = $parameters['status'];
        $this->message = $parameters['message'];
        $this->transactionRef = $parameters['data']['transaction_reference'];
        $this->requestRef = $parameters['data']['request_reference'];
        $this->amount = $parameters['data']['amount'];
        $this->recipientAccountNumber = $parameters['data']['destination_account_number'];
        $this->senderAccountNumber = $parameters['data']['sender_account_number'];
        $this->description = $parameters['data']['description'];
    }
}