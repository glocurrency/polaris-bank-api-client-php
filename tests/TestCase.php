<?php

namespace Glocurrency\PolarisBank\Tests;

/**
 * @author Che Dilas Yusuph <josephdilas@lovetechnigeria.com.ng>
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }
}
