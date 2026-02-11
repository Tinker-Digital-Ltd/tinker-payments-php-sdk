<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Api\SubscriptionManager;
use Tinker\Api\TransactionManager;
use Tinker\TinkerPayments;

final class TinkerPaymentsTest extends TestCase
{
    public function testTinkerPaymentsInitializesWithDefaultHttpClient(): void
    {
        $tinker = new TinkerPayments('pk_test_123', 'sk_test_456');

        $this->assertInstanceOf(TinkerPayments::class, $tinker);
    }

    public function testTinkerPaymentsAcceptsCustomHttpClient(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $requestFactory = $this->createMock(RequestFactoryInterface::class);

        $tinker = new TinkerPayments(
            'pk_test_123',
            'sk_test_456',
            $httpClient,
            $requestFactory,
        );

        $this->assertInstanceOf(TinkerPayments::class, $tinker);
    }

    public function testTinkerPaymentsAcceptsCustomBaseUrl(): void
    {
        $tinker = new TinkerPayments(
            'pk_test_123',
            'sk_test_456',
            null,
            null,
            'http://localhost:8080',
        );

        $this->assertInstanceOf(TinkerPayments::class, $tinker);
    }

    public function testTransactionsReturnsTransactionManager(): void
    {
        $tinker = new TinkerPayments('pk_test_123', 'sk_test_456');

        $this->assertInstanceOf(TransactionManager::class, $tinker->transactions());
    }

    public function testTransactionsReturnsSameInstanceOnMultipleCalls(): void
    {
        $tinker = new TinkerPayments('pk_test_123', 'sk_test_456');

        $transactions1 = $tinker->transactions();
        $transactions2 = $tinker->transactions();

        $this->assertSame($transactions1, $transactions2);
    }

    public function testSubscriptionsReturnsSubscriptionManager(): void
    {
        $tinker = new TinkerPayments('pk_test_123', 'sk_test_456');

        $this->assertInstanceOf(SubscriptionManager::class, $tinker->subscriptions());
    }

    public function testLastAuthMetaIsNullBeforeAuthentication(): void
    {
        $tinker = new TinkerPayments('pk_test_123', 'sk_test_456');

        $this->assertNull($tinker->getLastAuthMeta());
    }
}
