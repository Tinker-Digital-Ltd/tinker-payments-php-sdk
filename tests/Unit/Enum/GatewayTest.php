<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tinker\Enum\Gateway;

final class GatewayTest extends TestCase
{
    public function testGatewayMpesaValue(): void
    {
        $this->assertSame('mpesa', Gateway::MPESA->value);
    }

    public function testGatewayFromString(): void
    {
        $gateway = Gateway::from('mpesa');
        $this->assertSame(Gateway::MPESA, $gateway);
    }
}
