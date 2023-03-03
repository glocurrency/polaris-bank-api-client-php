<?php

namespace Glocurrency\PolarisBank\Interfaces;

/**
 * @author Che Dilas Yusuph <josephdilas@lovetechnigeria.com.ng>
 */

 interface ConfigInterface
{
    public function getBaseUrl(): string;
    public function getKey(): string;
    public function getClientSecret(): string;
}
