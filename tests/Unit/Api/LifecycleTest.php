<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tinker\Api\PayoutManager;
use Tinker\Api\TransactionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Exception\InvalidPayloadException;
use Tinker\Http\RequestFactory;
use Tinker\Model\DTO\CancellationRequest;
use Tinker\Model\DTO\PayoutRequest;
use Tinker\Model\DTO\RefundRequest;

final class LifecycleTest extends TestCase
{
    private function fixtures(): array
    {
        $path = getenv('TINKER_LIFECYCLE_FIXTURES') ?: __DIR__.'/../../Fixtures/merchant-lifecycle.json';

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function managers(array $responses, array &$history): array
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        $client = new Client(['handler' => $stack]);
        $config = new Configuration('pk', 'sk', 'https://example.test/v1');
        $auth = $this->createMock(AuthenticationManager::class);
        $auth->method('getToken')->willReturn('token');

        return [new TransactionManager($config, $client, new RequestFactory(), $auth), new PayoutManager($config, $client, new RequestFactory(), $auth)];
    }

    public function testSharedWireContractAndPersistedStates(): void
    {
        $fixtures = $this->fixtures();
        foreach ($fixtures['cases'] as $case) {
            $history = [];
            [$transactions, $payouts] = $this->managers([new Response($case['status'], [], json_encode($case['response']))], $history);
            $payment = $fixtures['payment_id'];
            $key = $fixtures['key'];
            $result = match ($case['operation']) {
                'refund' => $transactions->refund($payment, new RefundRequest('requested_by_customer', 100), $key),
                'refunds' => $transactions->refunds($payment),
                'get_refund' => $transactions->getRefund($payment, $fixtures['refund_id']),
                'payout_methods' => $payouts->methods(),
                'payout_quote' => $payouts->quote($fixtures['payout_method_id'], 100, 'JPY'),
                'request_payout' => $payouts->requestPayout(new PayoutRequest($fixtures['payout_method_id'], 100, 'JPY', 2), $key),
                'payouts' => $payouts->list(['page' => 2, 'per_page' => 10, 'status' => 'pending_approval', 'currency' => 'JPY']),
                'get_payout' => $payouts->get($fixtures['payout_id']),
                'cancel_checkout' => $transactions->cancelCheckout($payment, new CancellationRequest($case['body']['reason']), $key),
                'cancellations' => $transactions->cancellations($payment),
                'get_cancellation' => $transactions->getCancellation($payment, $fixtures['cancellation_id']),
            };
            $request = $history[0]['request'];
            self::assertSame($case['method'], $request->getMethod());
            $actual = parse_url((string) $request->getUri());
            $expected = parse_url('https://example.test/v1/'.$case['path']);
            self::assertSame($expected['path'], $actual['path']);
            parse_str($actual['query'] ?? '', $actualQuery);
            parse_str($expected['query'] ?? '', $expectedQuery);
            self::assertEquals($expectedQuery, $actualQuery);
            self::assertSame('Bearer token', $request->getHeaderLine('Authorization'));
            self::assertSame($case['headers']['Idempotency-Key'] ?? '', $request->getHeaderLine('Idempotency-Key'));
            self::assertEquals($case['body'], json_decode((string) $request->getBody(), true));
            if ('request_payout' === $case['operation']) {
                self::assertSame('pending_approval', $result->withdrawal->getStatus());
                self::assertEquals(98, $result->withdrawal->toArray()['net_amount']);
            } elseif ('cancel_checkout' === $case['operation']) {
                self::assertSame('outcome_unknown', $result->cancellation->getStatus());
            } elseif ('refund' === $case['operation']) {
                self::assertSame('pending_review', $result->refund->getStatus());
            } elseif ('payout_methods' === $case['operation']) {
                self::assertSame($case['response']['data'], $result);
            }
        }
    }

    public function testUnsupportedCancellationRetainsRecordedRequestDetails(): void
    {
        $history = [];
        $details = ['request_id' => 'cancel-1', 'status' => 'rejected'];
        [$transactions] = $this->managers([new Response(422, [], json_encode(['success' => false, 'error' => ['code' => 'CHECKOUT_CANCELLATION_UNSUPPORTED', 'message' => 'Unsupported', 'details' => $details]]))], $history);
        try {
            $transactions->cancelCheckout('payment', new CancellationRequest('Changed mind'), 'key');
            self::fail('Expected an API error');
        } catch (ApiException $e) {
            self::assertSame(422, $e->httpStatus);
            self::assertSame($details, $e->details);
        }
        self::assertCount(1, $history);
    }

    public function testSharedErrorsRetainDetailsWithoutRetry(): void
    {
        foreach ($this->fixtures()['errors'] as $case) {
            $history = [];
            [$transactions] = $this->managers([new Response($case['status'], [], json_encode($case['response']))], $history);
            try {
                $transactions->cancelCheckout('payment', new CancellationRequest('Changed mind'), 'key');
                self::fail('Expected an API error');
            } catch (ApiException $e) {
                self::assertSame($case['status'], $e->httpStatus);
                self::assertSame($case['response']['error']['code'], $e->errorCode);
                self::assertSame($case['response']['error']['details'], $e->details);
            }
            self::assertCount(1, $history);
        }
    }

    public function testFixtureMatchesCanonicalWhenAvailable(): void
    {
        $canonical = __DIR__.'/../../../../tinker-payments-openapi/fixtures/merchant-lifecycle.json';
        if (is_file($canonical)) {
            self::assertSame(json_decode(file_get_contents($canonical), true, flags: JSON_THROW_ON_ERROR), $this->fixtures());
        } else {
            self::assertSame(1, $this->fixtures()['contract_version']);
        }
    }

    public function testInvalidKeyNeverSendsMutation(): void
    {
        $history = [];
        [$transactions] = $this->managers([], $history);
        try {
            $transactions->refund('payment', new RefundRequest('other'), ' ');
            self::fail('Expected invalid key');
        } catch (InvalidPayloadException) {
            self::assertSame([], $history);
        }
    }
}
