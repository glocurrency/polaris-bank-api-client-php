<?php

namespace Glocurrency\PolarisBank\Interfaces;

/**
 * @author Che Dilas Yusuph <josephdilas@lovetechnigeria.com.ng>
 */

 interface ConfigInterface
{
    public function getApiBaseUrl(): string;
    public function getApiKey(): string;
    public function getClientSecret(): string;
    public function getAppId(): string;
    public function getAuthToken(): string;
    public function getCacheTtl();
    public function getSignature(): string;
}
