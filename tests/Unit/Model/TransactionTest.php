<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tinker\Enum\PaymentStatus;
use Tinker\Model\Transaction;

final class TransactionTest extends TestCase
{
    public function testTransactionInitializesWithData(): void
    {
        $data = [
            'payment_reference' => 'TXN-abc123xyz',
            'status' => 'pending',
            'authorization_url' => null,
        ];

        $transaction = new Transaction($data);

        $this->assertSame('TXN-abc123xyz', $transaction->payment_reference);
        $this->assertSame(PaymentStatus::PENDING, $transaction->status);
        $this->assertNull($transaction->authorization_url);
    }

    public function testTransactionHandlesAuthorizationUrl(): void
    {
        $data = [
            'payment_reference' => 'TXN-abc123xyz',
            'status' => 'pending',
            'authorization_url' => 'https://example.com/auth',
        ];

        $transaction = new Transaction($data);

        $this->assertSame('https://example.com/auth', $transaction->authorization_url);
    }

    public function testIsSuccessfulReturnsTrueForSuccessfulStatus(): void
    {
        $transaction = new Transaction(['status' => 'success']);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertSame(PaymentStatus::SUCCESS, $transaction->status);
    }

    public function testIsSuccessfulReturnsFalseForNonSuccessfulStatus(): void
    {
        $transaction = new Transaction(['status' => 'pending']);

        $this->assertFalse($transaction->isSuccessful());
    }

    public function testIsPendingReturnsTrueForPendingStatus(): void
    {
        $transaction = new Transaction(['status' => 'pending']);

        $this->assertTrue($transaction->isPending());
        $this->assertSame(PaymentStatus::PENDING, $transaction->status);
    }

    public function testIsPendingReturnsFalseForNonPendingStatus(): void
    {
        $transaction = new Transaction(['status' => 'success']);

        $this->assertFalse($transaction->isPending());
    }

    public function testIsCancelledReturnsTrueForCancelledStatus(): void
    {
        $transaction = new Transaction(['status' => 'cancelled']);

        $this->assertTrue($transaction->isCancelled());
        $this->assertSame(PaymentStatus::CANCELLED, $transaction->status);
    }

    public function testIsFailedReturnsTrueForFailedStatus(): void
    {
        $transaction = new Transaction(['status' => 'failed']);

        $this->assertTrue($transaction->isFailed());
        $this->assertSame(PaymentStatus::FAILED, $transaction->status);
    }
}
