<?php

// Copyright (C) 2022 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace GloCurrency\PolarisBank;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\ClientInterface;
use GloCurrency\PolarisBank\Models\TransactionResponse;
use GloCurrency\PolarisBank\Models\QueryTransactionResponse;
use GloCurrency\PolarisBank\Models\LookupAccountResponse;
use GloCurrency\PolarisBank\Interfaces\TransactionInterface;
use GloCurrency\PolarisBank\Interfaces\ConfigInterface;
use BrokeYourBike\ResolveUri\ResolveUriTrait;
use BrokeYourBike\HttpEnums\HttpMethodEnum;
use BrokeYourBike\HttpClient\HttpClientTrait;
use BrokeYourBike\HttpClient\HttpClientInterface;
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\HasSourceModel\HasSourceModelTrait;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class Client implements HttpClientInterface
{
    use HttpClientTrait;
    use ResolveUriTrait;
    use HasSourceModelTrait;

    private ConfigInterface $config;

    public function __construct(ConfigInterface $config, ClientInterface $httpClient)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /** @link https://docs.openbanking.vulte.ng/#65802ea5-bf82-499b-8cde-f7463724aaea */
    public function disburse(string $requestReference, TransactionInterface $transaction): TransactionResponse
    {
        if ($transaction instanceof SourceModelInterface){
            $this->setSourceModel($transaction);
        }

        $data = [
            'request_ref' => $requestReference,
            'request_type' => 'disburse',
            'auth' => [
                'auth_provider' => 'Polaris',
            ],
            'transaction' => [
                'mock_mode' => $this->config->isMock() ? 'Inspect' : 'Live',
                'transaction_ref' => $transaction->getReference(),
                'amount' => $transaction->getAmountInCents(),
                'customer' => [
                    'customer_ref' => $transaction->getRecipientId()
                ],
                'meta' => [
                    'use_usd' => $transaction->getCurrencyCode() === 'USD',
                    'currency' => $transaction->getCurrencyCode(),
                ],
                'details' => [
                    'destination_account' => $transaction->getBankAccount(),
                    'destination_bank' => $transaction->getBankCode(),
                ],
            ],
        ];

        $response = $this->performRequest($requestReference, HttpMethodEnum::POST, 'v2/transact', $data);
        return new TransactionResponse($response);
    }

    /** @link https://docs.openbanking.vulte.ng/#d6897e92-e417-49af-a52b-b3fb1e731c0f */
    public function queryTransaction(string $requestReference, TransactionInterface $transaction): QueryTransactionResponse
    {
        if ($transaction instanceof SourceModelInterface){
            $this->setSourceModel($transaction);
        }

        $data = [
            'request_ref' => $requestReference,
            'request_type' => 'disburse',
            'auth' => [
                'auth_provider' => 'Polaris',
            ],
            'transaction' => [
                'transaction_ref' => $transaction->getReference(),
            ],
        ];

        $response = $this->performRequest($requestReference, HttpMethodEnum::POST, 'v2/transact/query', $data);
        return new QueryTransactionResponse($response);
    }

    /** @link https://docs.openbanking.vulte.ng/#a677e46d-4d2d-475a-b609-49649c3125ca */
    public function lookupTransactionAccount(string $requestReference, TransactionInterface $transaction): LookupAccountResponse
    {
        $data = [
            'request_ref' => $requestReference,
            'request_type' => 'lookup_account_min',
            'auth' => [
                'type' => 'bank.account',
                'secure' => $this->encryptData("{$transaction->getBankCode()};{$transaction->getBankAccount()}"),
                'auth_provider' => 'Polaris',
            ],
            'transaction' => [
                'mock_mode' => $this->config->isMock() ? 'Inspect' : 'Live',
                'transaction_ref' => $requestReference,
                'customer' => [
                    'customer_ref' => $transaction->getRecipientId()
                ],
            ],
        ];

        $response = $this->performRequest($requestReference, HttpMethodEnum::POST, 'v2/transact', $data);
        return new LookupAccountResponse($response);
    }

    /** @link https://docs.openbanking.vulte.ng/#encryption-of-secure-element */
    public function encryptData(string $data): string
    {
        $source = \mb_convert_encoding($this->config->getSecret(), 'UTF-16LE', 'UTF-8');
        $key = \md5($source, true);
        $key .= \substr($key, 0, 8);

        $des = new \phpseclib3\Crypt\TripleDES('cbc');
        $des->setKey($key);
        $des->setIV("\0\0\0\0\0\0\0\0");
        return base64_encode($des->encrypt($data));
    }

    /**
     * @param string $requestReference
     * @param HttpMethodEnum $method
     * @param string $uri
     * @param array<mixed> $data
     * @return ResponseInterface
     */
    private function performRequest(string $requestReference, HttpMethodEnum $method, string $uri, array $data): ResponseInterface
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->config->getToken()}",
                'Signature' => \md5("{$requestReference};{$this->config->getSecret()}"),
            ],
            \GuzzleHttp\RequestOptions::JSON => $data,
        ];

        if ($this->getSourceModel()) {
            $options[\BrokeYourBike\HasSourceModel\Enums\RequestOptions::SOURCE_MODEL] = $this->getSourceModel();
        }

        $uri = (string) $this->resolveUriFor($this->config->getUrl(), $uri);
        return $this->httpClient->request($method->value, $uri, $options);
    }
}
