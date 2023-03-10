<?php

// Copyright (C) 2022 Ivan Stasiuk <ivan@stasi.uk>.
//
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this file,
// You can obtain one at https://mozilla.org/MPL/2.0/.

namespace GloCurrency\PolarisBank\Enums;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 * @link https://docs.openbanking.vulte.ng/#what-responses-would-look-like
 */
enum ErrorCodeEnum: string
{
    /**
     * For all requests that were successfully processed.
     */
    case SUCCESSFUL = 'Successful';

    /**
     * If a request fails. Read the errors object(s).
     */
    case FAILED = 'Failed';

    /**
     * If a request requires OTP validation for completion.
     */
    case WAITING_FOR_OTP = 'WaitingForOTP';

    /**
     * If a request requires other information to be supplied for completion.
     */
    case PENDING_VALIDATION = 'PendingValidation';

    /**
     * If a transaction request is still in a processing state and needs to be subsequently queried.
     */
    case PROCESSING = 'Processing';

    /**
     * Applicable only for services that support some form of options processing.
     */
    case OPTIONS_DELIVERED = 'OptionsDelivered';

    /**
     * If an ID being looked up by service is not valid.
     */
    case INVALID_ID = 'InvalidID';

    /**
     * If a request is flagged as suspicious.
     */
    case FRAUD = 'Fraud';

    /**
     * If a similar request has been made earlier within a stipulated time frame of 5 minutes.
     */
    case DUPLICATE = 'Duplicate';
}
