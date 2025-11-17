<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tinker\Enum\PaymentStatus;

final class PaymentStatusTest extends TestCase
{
    public function testPaymentStatusValues(): void
    {
        $this->assertSame('pending', PaymentStatus::PENDING->value);
        $this->assertSame('success', PaymentStatus::SUCCESS->value);
        $this->assertSame('cancelled', PaymentStatus::CANCELLED->value);
        $this->assertSame('failed', PaymentStatus::FAILED->value);
    }

    public function testPaymentStatusFromString(): void
    {
        $this->assertSame(PaymentStatus::PENDING, PaymentStatus::from('pending'));
        $this->assertSame(PaymentStatus::SUCCESS, PaymentStatus::from('success'));
        $this->assertSame(PaymentStatus::CANCELLED, PaymentStatus::from('cancelled'));
        $this->assertSame(PaymentStatus::FAILED, PaymentStatus::from('failed'));
    }
}
