<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tinker\Model\Transaction;

final class TransactionTest extends TestCase
{
    public function testTransactionInitializesWithData(): void
    {
        $data = [
            'id' => 'txn_123',
            'amount' => 1000.00,
            'currency' => 'KES',
            'status' => 'pending',
            'metadata' => ['order_id' => 'order_123'],
            'createdAt' => '2025-01-01T00:00:00Z',
        ];

        $transaction = new Transaction($data);

        $this->assertSame('txn_123', $transaction->id);
        $this->assertSame(1000.00, $transaction->amount);
        $this->assertSame('KES', $transaction->currency);
        $this->assertSame('pending', $transaction->status);
        $this->assertSame(['order_id' => 'order_123'], $transaction->metadata);
        $this->assertSame('2025-01-01T00:00:00Z', $transaction->createdAt);
    }

    public function testIsSuccessfulReturnsTrueForSuccessfulStatus(): void
    {
        $transaction = new Transaction(['status' => 'successful']);

        $this->assertTrue($transaction->isSuccessful());
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
    }

    public function testIsPendingReturnsFalseForNonPendingStatus(): void
    {
        $transaction = new Transaction(['status' => 'successful']);

        $this->assertFalse($transaction->isPending());
    }
}

