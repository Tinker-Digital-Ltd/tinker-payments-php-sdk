<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Tinker\Enum\PaymentStatus;
use Tinker\Model\Transaction;

final class TransactionTest extends TestCase
{
    public function testTransactionInitializesWithInitiationData(): void
    {
        $data = [
            'payment_reference' => 'TXN-abc123xyz',
            'status' => 'pending',
            'authorization_url' => null,
        ];

        $transaction = new Transaction($data);

        $initiationData = $transaction->getInitiationData();
        $this->assertNotNull($initiationData);
        $this->assertSame('TXN-abc123xyz', $initiationData->paymentReference);
        $this->assertSame(PaymentStatus::PENDING, $initiationData->status);
        $this->assertNull($initiationData->authorizationUrl);
        $this->assertSame(PaymentStatus::PENDING, $transaction->status);
        $this->assertNull($transaction->getQueryData());
        $this->assertNull($transaction->getCallbackData());
    }

    public function testTransactionHandlesAuthorizationUrl(): void
    {
        $data = [
            'payment_reference' => 'TXN-abc123xyz',
            'status' => 'pending',
            'authorization_url' => 'https://example.com/auth',
        ];

        $transaction = new Transaction($data);

        $initiationData = $transaction->getInitiationData();
        $this->assertNotNull($initiationData);
        $this->assertSame('https://example.com/auth', $initiationData->authorizationUrl);
    }

    public function testTransactionInitializesWithQueryData(): void
    {
        $data = [
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ];

        $transaction = new Transaction($data);

        $queryData = $transaction->getQueryData();
        $this->assertNotNull($queryData);
        $this->assertSame('pay_abc123', $queryData->id);
        $this->assertSame('TXN-abc123xyz', $queryData->reference);
        $this->assertSame(100.00, $queryData->amount);
        $this->assertSame('KES', $queryData->currency);
        $this->assertSame(PaymentStatus::SUCCESS, $queryData->status);
        $this->assertSame('mpesa', $queryData->channel);
        $this->assertSame(PaymentStatus::SUCCESS, $transaction->status);
        $this->assertNull($transaction->getInitiationData());
    }

    public function testTransactionInitializesWithCallbackData(): void
    {
        $data = [
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ];

        $transaction = new Transaction($data);

        $callbackData = $transaction->getCallbackData();
        $this->assertNotNull($callbackData);
        $this->assertSame('pay_abc123', $callbackData->id);
        $this->assertSame('TXN-abc123xyz', $callbackData->reference);
        $this->assertSame(100.00, $callbackData->amount);
        $this->assertSame('KES', $callbackData->currency);
        $this->assertSame(PaymentStatus::SUCCESS, $callbackData->status);
    }

    public function testInitiationDataToArray(): void
    {
        $data = [
            'payment_reference' => 'TXN-abc123xyz',
            'status' => 'pending',
            'authorization_url' => 'https://example.com/auth',
        ];

        $transaction = new Transaction($data);
        $initiationData = $transaction->getInitiationData();
        $this->assertNotNull($initiationData);

        $array = $initiationData->toArray();
        $this->assertSame('TXN-abc123xyz', $array['payment_reference']);
        $this->assertSame('pending', $array['status']);
        $this->assertSame('https://example.com/auth', $array['authorization_url']);
    }

    public function testQueryDataToArray(): void
    {
        $data = [
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ];

        $transaction = new Transaction($data);
        $queryData = $transaction->getQueryData();
        $this->assertNotNull($queryData);

        $array = $queryData->toArray();
        $this->assertSame('pay_abc123', $array['id']);
        $this->assertSame('success', $array['status']);
        $this->assertSame('TXN-abc123xyz', $array['reference']);
        $this->assertSame(100.00, $array['amount']);
        $this->assertSame('KES', $array['currency']);
        $this->assertSame('2024-01-15T10:30:00Z', $array['paid_at']);
        $this->assertSame('2024-01-15T10:25:00Z', $array['created_at']);
        $this->assertSame('mpesa', $array['channel']);
    }

    public function testCallbackDataToArray(): void
    {
        $data = [
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ];

        $transaction = new Transaction($data);
        $callbackData = $transaction->getCallbackData();
        $this->assertNotNull($callbackData);

        $array = $callbackData->toArray();
        $this->assertSame('pay_abc123', $array['id']);
        $this->assertSame('success', $array['status']);
        $this->assertSame('TXN-abc123xyz', $array['reference']);
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
