<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Tinker\Api\SubscriptionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Http\RequestFactory;
use Tinker\Http\Response;
use Tinker\Model\DTO\ManagedCheckout;

final class ManagedBillingTest extends TestCase
{
    public function testAllOperationsPreservePayloadsAndPagination(): void
    {
        $calls = [];
        $response = new Response();
        $response->getBody()->write('{"success":true,"data":{"snapshot":123,"next_cursor":"in_next","has_more":true}}');
        $http = $this->createMock(ClientInterface::class);
        $http->method('sendRequest')->willReturnCallback(static function (RequestInterface $request) use (&$calls, $response) {
            $calls[] = [$request->getMethod(), (string) $request->getUri(), json_decode((string) $request->getBody(), true), $request->getHeaderLine('Authorization')];

            $response->getBody()->rewind();

            return $response;
        });
        $m = $this->manager($http);
        $checkout = new ManagedCheckout('workspace', 'buyer@example.com', 'Buyer', 'EUR', 15000, 3, 'year', 'https://example.com/return', 'checkout-key');
        self::assertSame([
            'external_customer_id' => 'workspace', 'email' => 'buyer@example.com', 'name' => 'Buyer',
            'currency' => 'EUR', 'unit_amount' => 15000, 'quantity' => 3, 'interval' => 'year',
            'return_url' => 'https://example.com/return', 'idempotency_key' => 'checkout-key',
        ], $checkout->toArray());
        $m->checkout($checkout);
        $m->billingResource('sub/a b');
        $m->changeQuantity('sub/a b', 4, 'change-key');
        $m->scheduleQuantity('sub/a b', 2, 'schedule-key');
        $m->clearScheduledQuantity('sub/a b', 'clear-key');
        $m->setCancelAtRenewal('sub/a b', false, 'resume-key');
        $m->billingOperation('key/a b');
        $m->paymentMethodPortal('sub/a b', 'https://example.com/return', 'portal-key');
        $page = $m->billingPaymentHistory('sub/a b', 'in/a &b', 123);
        $m->billingPaymentHistory('sub/a b');
        $base = 'merchant/billing/resources/sub%2Fa%20b';
        $expected = [
            ['POST', 'merchant/billing/checkout', $checkout->toArray()],
            ['GET', $base, null],
            ['POST', $base.'/quantity', ['quantity' => 4, 'idempotency_key' => 'change-key']],
            ['POST', $base.'/schedule', ['quantity' => 2, 'idempotency_key' => 'schedule-key']],
            ['POST', $base.'/unschedule', ['idempotency_key' => 'clear-key']],
            ['POST', $base.'/cancel', ['cancel' => false, 'idempotency_key' => 'resume-key']],
            ['GET', 'merchant/billing/operations/key%2Fa%20b', null],
            ['POST', $base.'/payment-method', ['return_url' => 'https://example.com/return', 'idempotency_key' => 'portal-key']],
            ['GET', $base.'/payment-history?starting_after=in%2Fa%20%26b&snapshot=123', null],
            ['GET', $base.'/payment-history', null],
        ];
        $actual = [];
        foreach ($calls as [$method, $url, $body, $authorization]) {
            self::assertSame('Bearer token', $authorization);
            $actual[] = [$method, explode('/v1/', $url)[1], $body];
        }
        self::assertSame($expected, $actual);
        self::assertSame(['snapshot' => 123, 'next_cursor' => 'in_next', 'has_more' => true], $page);
    }

    public function testStructuredAndUncertainFailuresPreserveMetadata(): void
    {
        foreach ([429, 200, 502] as $status) {
            $response = (new Response($status))->withHeader('x-request-id', 'header-id')->withHeader('rEtRy-AfTeR', '30');
            $response->getBody()->write(502 === $status ? '<html>Bad gateway</html>' : '{"success":false,"error":{"message":"Try later","code":"RATE_LIMITED","provider_code":"rate_limit","request_id":"body-id","outcome":"not_applied"}}');
            $http = $this->createMock(ClientInterface::class);
            $http->method('sendRequest')->willReturn($response);
            try {
                $this->manager($http)->listPlans();
                self::fail('Expected API failure');
            } catch (ApiException $error) {
                self::assertSame($status, $error->httpStatus);
                self::assertSame('30', $error->retryAfter);
                self::assertSame(502 === $status ? 'header-id' : 'body-id', $error->requestId);
                self::assertSame(502 === $status ? null : 'RATE_LIMITED', $error->errorCode);
                self::assertSame(502 === $status ? null : 'rate_limit', $error->providerCode);
                self::assertSame(502 === $status ? null : 'not_applied', $error->outcome);
            }
        }
        $legacy = new ApiException('legacy', 123);
        self::assertSame(123, $legacy->getCode());
        self::assertSame('legacy', $legacy->getMessage());
    }

    private function manager(ClientInterface $http): SubscriptionManager
    {
        $config = new Configuration('public', 'secret');
        $auth = $this->createMock(AuthenticationManager::class);
        $auth->method('getToken')->willReturn('token');

        return new SubscriptionManager($config, $http, new RequestFactory(), $auth);
    }
}
