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
use Tinker\Api\SubscriptionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Model\DTO\CreateSubscriptionPlanRequest;
use Tinker\Model\DTO\CreateSubscriptionRequest;
use Tinker\Model\DTO\SubscriptionCustomer;

final class SubscriptionManagerTest extends TestCase
{
    private Configuration $config;
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $requestFactory;
    private AuthenticationManager&MockObject $authManager;
    private SubscriptionManager $subscriptionManager;

    protected function setUp(): void
    {
        $this->config = new Configuration('pk_test_123', 'sk_test_456');
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->authManager = $this->createMock(AuthenticationManager::class);
        $this->authManager->method('getToken')->willReturn('test_token_123');
        $this->subscriptionManager = new SubscriptionManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function testCreatePlanReturnsUnwrappedData(): void
    {
        $request = $this->mockRequest();
        $response = $this->mockResponse([
            'success' => true,
            'data' => [
                'id' => 'plan_123',
                'name' => 'Pro Monthly',
            ],
        ]);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/merchant/subscriptions/plans'))
            ->willReturn($request);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $result = $this->subscriptionManager->createPlan(
            new CreateSubscriptionPlanRequest(
                name: 'Pro Monthly',
                amount: 29.99,
                currency: 'USD',
                intervals: ['monthly'],
            ),
        );

        $this->assertSame('plan_123', $result['id']);
    }

    public function testCreateSubscriptionReturnsUnwrappedData(): void
    {
        $request = $this->mockRequest();
        $response = $this->mockResponse([
            'success' => true,
            'data' => [
                'subscription_id' => 'sub_456',
                'status' => 'active',
            ],
        ]);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/merchant/subscriptions'))
            ->willReturn($request);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $result = $this->subscriptionManager->create(
            new CreateSubscriptionRequest(
                planId: 'plan_123',
                gateway: 'stripe',
                customer: new SubscriptionCustomer(
                    externalCustomerId: 'cust_001',
                    name: 'Jane Doe',
                    email: 'jane@example.com',
                ),
                billingPeriod: 'monthly',
            ),
        );

        $this->assertSame('sub_456', $result['subscription_id']);
    }

    public function testListSubscriptionsWithFilters(): void
    {
        $request = $this->mockRequest();
        $response = $this->mockResponse([
            'success' => true,
            'data' => [
                ['id' => 'sub_1'],
            ],
        ]);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with(
                'GET',
                $this->callback(
                    static fn (string $url): bool => str_contains($url, '/merchant/subscriptions?')
                        && str_contains($url, 'plan_id=plan_123')
                        && str_contains($url, 'external_customer_id=cust_001'),
                ),
            )
            ->willReturn($request);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $result = $this->subscriptionManager->list('plan_123', 'cust_001');

        $this->assertCount(1, $result);
        $this->assertSame('sub_1', $result[0]['id']);
    }

    public function testCancelSubscriptionReturnsMessage(): void
    {
        $request = $this->mockRequest();
        $response = $this->mockResponse([
            'success' => true,
            'data' => [
                'message' => 'Subscription cancelled successfully',
            ],
        ]);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/merchant/subscriptions/sub_456/cancel'))
            ->willReturn($request);

        $this->httpClient->method('sendRequest')->willReturn($response);

        $result = $this->subscriptionManager->cancel('sub_456');

        $this->assertSame('Subscription cancelled successfully', $result['message']);
    }

    private function mockRequest(): RequestInterface&MockObject
    {
        $request = $this->createMock(RequestInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturnCallback(static function (): void {});

        return $request;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function mockResponse(array $payload): ResponseInterface&MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn((string) json_encode($payload));

        return $response;
    }
}
