<?php

namespace Glocurrency\PolarisBank\Models;

use Spatie\DataTransferObject\Attributes\MapFrom;
use BrokeYourBike\DataTransferObject\JsonResponse;

class FetchAuthTokenResponse extends JsonResponse
{
    #[MapFrom('expires_in')]
    public int $expiresIn;

    #[MapFrom('access_token')]
    public string $accessToken;
}
