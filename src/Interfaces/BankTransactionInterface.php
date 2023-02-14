<?php

namespace GloCurrency\PolarisBank\Interfaces;

/**
 * Represents a bank transaction request.
 */
interface BankTransactionInterface
{
    public function getRequestRef(): string;
    public function getRequestType(): string;
    public function getAuthType(): string;
    public function getSecure(): string;
    public function getAuthProvider(): string;
    public function getRouteMode(): ?string;
    public function getMockMode(): string;
    public function getTransactionRef(): string;
    public function getTransactionDesc(): string;
    public function getTransactionRefParent(): ?string;
    public function getAmount(): int;
    public function getCustomerRef(): string;
    public function getFirstName(): string;
    public function getSurname(): string;
    public function getEmail(): string;
    public function getMobileNo(): string;
    public function getMeta(): array;
    public function getDetails(): ?array;
}