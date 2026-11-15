<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Api;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tinker\Api\CustomerManager;
use Tinker\Api\SubscriptionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;

final class CustomerManagerTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $requestFactory;
    private StreamInterface&MockObject $body;
    private CustomerManager $customers;
    private SubscriptionManager $subscriptions;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $authManager = $this->createMock(AuthenticationManager::class);
        $authManager->method('getToken')->willReturn('test_token_123');
        $config = new Configuration('pk_test_123', 'sk_test_456');
        $this->customers = new CustomerManager($config, $this->httpClient, $this->requestFactory, $authManager);
        $this->subscriptions = new SubscriptionManager($config, $this->httpClient, $this->requestFactory, $authManager);
    }

    public function testGetCustomerEncodesExternalCustomerId(): void
    {
        $this->expectRequest('GET', '/v1/merchant/customers/ws%2F1%20a');
        $this->respondWith(['external_customer_id' => 'ws/1 a', 'entitlements' => [], 'subscriptions' => []]);

        $result = $this->customers->getCustomer('ws/1 a');

        $this->assertSame('ws/1 a', $result['external_customer_id']);
    }

    public function testGetEntitlementReturnsInactive(): void
    {
        $this->expectRequest('GET', '/v1/merchant/customers/ws%2F1/entitlements/pro%20plus');
        $this->respondWith(['key' => 'pro plus', 'active' => false, 'expires_at' => null, 'is_trial' => false, 'in_grace' => false]);

        $result = $this->customers->getEntitlement('ws/1', 'pro plus');

        $this->assertFalse($result['active']);
    }

    public function testGetPlanEntitlements(): void
    {
        $this->expectRequest('GET', '/v1/merchant/subscriptions/plans/plan_1/entitlements');
        $this->respondWith(['plan_id' => 'plan_1', 'keys' => ['pro']]);

        $this->assertSame(['pro'], $this->subscriptions->getPlanEntitlements('plan_1')['keys']);
    }

    public function testSetPlanEntitlementsSendsKeysBody(): void
    {
        $this->expectRequest('PUT', '/v1/merchant/subscriptions/plans/plan_1/entitlements');
        $this->body->expects($this->once())->method('write')->with('{"keys":["pro","export"]}');
        $this->respondWith(['plan_id' => 'plan_1', 'keys' => ['pro', 'export']]);

        $this->assertSame(['pro', 'export'], $this->subscriptions->setPlanEntitlements('plan_1', ['pro', 'export'])['keys']);
    }

    private function expectRequest(string $method, string $path): void
    {
        $request = $this->createMock(RequestInterface::class);
        $this->body = $this->createMock(StreamInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($this->body);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with($method, $this->stringEndsWith($path))
            ->willReturn($request);
    }

    /** @param array<string, mixed> $data */
    private function respondWith(array $data): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn((string) json_encode(['success' => true, 'data' => $data]));
        $this->httpClient->method('sendRequest')->willReturn($response);
    }
}
