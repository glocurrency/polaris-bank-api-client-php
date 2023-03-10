<?php

// Copyright (C) 2022 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace GloCurrency\PolarisBank\Models;

use Spatie\DataTransferObject\Attributes\MapFrom;
use BrokeYourBike\DataTransferObject\JsonResponse;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class TransactionResponse extends JsonResponse
{
    public string $status;
    public string $message;

    #[MapFrom('data.error.code')]
    public ?string $errorCode;

    #[MapFrom('data.error.message')]
    public ?string $errorMessage;

    #[MapFrom('data.provider_response.reference')]
    public ?string $providerReference;
}

