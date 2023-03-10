<?php

// Copyright (C) 2022 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace GloCurrency\PolarisBank\Interfaces;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
interface TransactionInterface
{
    public function getReference(): string;
    public function getAmountInCents(): int;
    public function getCurrencyCode(): string;
    public function getBankCode(): string;
    public function getBankAccount(): string;
    public function getRecipientId(): string;
}
