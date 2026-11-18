<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tinker\Api\BaseManager;
use Tinker\Api\CustomerManager;
use Tinker\Api\EntitlementManager;
use Tinker\Api\ExperimentManager;
use Tinker\Api\IntegrationManager;
use Tinker\Api\MetricsManager;
use Tinker\Api\OfferingManager;
use Tinker\Api\ProductManager;
use Tinker\Api\SubscriptionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Model\DTO\InitiatePaymentRequest;
use Tinker\Webhook\DTO\CustomerEventData;
use Tinker\Webhook\DTO\EntitlementEventData;
use Tinker\Webhook\WebhookEvent;

final class CustomerManagerTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $requestFactory;
    private StreamInterface&MockObject $body;
    private AuthenticationManager&MockObject $auth;
    private Configuration $config;
    private CustomerManager $customers;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->auth = $this->createMock(AuthenticationManager::class);
        $this->auth->method('getToken')->willReturn('test_token_123');
        $this->config = new Configuration('pk_test_123', 'sk_test_456');
        $this->customers = new CustomerManager($this->config, $this->httpClient, $this->requestFactory, $this->auth);
    }

    public function testGetCustomerEncodesExternalCustomerId(): void
    {
        $this->expectRequest('GET', '/v1/merchant/customers/ws%2F1%20a');
        $this->respondWith(['external_customer_id' => 'ws/1 a']);

        $this->assertSame('ws/1 a', $this->customers->getCustomer('ws/1 a')['external_customer_id']);
    }

    public function testGetEntitlementReturnsInactive(): void
    {
        $this->expectRequest('GET', '/v1/merchant/customers/ws%2F1/entitlements/pro%20plus');
        $this->respondWith(['identifier' => 'pro plus', 'is_active' => false, 'expiration_date' => null]);

        $this->assertFalse($this->customers->getEntitlement('ws/1', 'pro plus')['is_active']);
    }

    /** @return iterable<string, array{Closure(class-string<BaseManager>): mixed, string, string, string|null}> */
    public static function calls(): iterable
    {
        $c = '/v1/merchant/customers/c1';
        $m = '/v1/merchant';

        yield 'grant' => [static fn (\Closure $t) => $t(CustomerManager::class)->grantPromotionalEntitlement('c1', 'pro', ['duration' => 'P1W']), 'POST', $c.'/entitlements/pro/promotional', '{"duration":"P1W"}'];
        yield 'revoke' => [static fn (\Closure $t) => $t(CustomerManager::class)->revokePromotionalEntitlements('c1', 'pro'), 'POST', $c.'/entitlements/pro/revoke_promotionals', null];
        yield 'alias' => [static fn (\Closure $t) => $t(CustomerManager::class)->alias('c1', 'anon'), 'POST', $c.'/alias', '{"alias":"anon"}'];
        yield 'transfer' => [static fn (\Closure $t) => $t(CustomerManager::class)->transfer('c1', 'c2'), 'POST', $c.'/transfer', '{"to":"c2"}'];
        yield 'attributes' => [static fn (\Closure $t) => $t(CustomerManager::class)->setAttributes('c1', ['plan' => 'gold', 'old' => null]), 'POST', $c.'/attributes', '{"attributes":{"plan":"gold","old":null}}'];
        yield 'offerings' => [static fn (\Closure $t) => $t(CustomerManager::class)->getOfferings('c1'), 'GET', $c.'/offerings', null];
        yield 'token' => [static fn (\Closure $t) => $t(CustomerManager::class)->createToken('c1', 600), 'POST', $c.'/token', '{"expires_in":600}'];
        yield 'entitlements list' => [static fn (\Closure $t) => $t(EntitlementManager::class)->list(), 'GET', $m.'/entitlements', null];
        yield 'entitlements create' => [static fn (\Closure $t) => $t(EntitlementManager::class)->create(['identifier' => 'pro']), 'POST', $m.'/entitlements', '{"identifier":"pro"}'];
        yield 'entitlements get' => [static fn (\Closure $t) => $t(EntitlementManager::class)->get('e/1'), 'GET', $m.'/entitlements/e%2F1', null];
        yield 'entitlements update' => [static fn (\Closure $t) => $t(EntitlementManager::class)->update('e1', 'Pro'), 'PATCH', $m.'/entitlements/e1', '{"display_name":"Pro"}'];
        yield 'entitlements delete' => [static fn (\Closure $t) => $t(EntitlementManager::class)->delete('e1'), 'DELETE', $m.'/entitlements/e1', null];
        yield 'entitlements attach' => [static fn (\Closure $t) => $t(EntitlementManager::class)->attachProducts('e1', ['p1']), 'POST', $m.'/entitlements/e1/attach_products', '{"product_ids":["p1"]}'];
        yield 'entitlements detach' => [static fn (\Closure $t) => $t(EntitlementManager::class)->detachProducts('e1', ['p1']), 'POST', $m.'/entitlements/e1/detach_products', '{"product_ids":["p1"]}'];
        yield 'products list' => [static fn (\Closure $t) => $t(ProductManager::class)->list(), 'GET', $m.'/products', null];
        yield 'products create' => [static fn (\Closure $t) => $t(ProductManager::class)->create(['name' => 'Pack', 'price' => 5, 'currency' => 'USD']), 'POST', $m.'/products', '{"name":"Pack","price":5,"currency":"USD"}'];
        yield 'products get' => [static fn (\Closure $t) => $t(ProductManager::class)->get('p1'), 'GET', $m.'/products/p1', null];
        yield 'products update' => [static fn (\Closure $t) => $t(ProductManager::class)->update('p1', ['is_active' => false]), 'PATCH', $m.'/products/p1', '{"is_active":false}'];
        yield 'offerings list' => [static fn (\Closure $t) => $t(OfferingManager::class)->list(), 'GET', $m.'/offerings', null];
        yield 'offerings create' => [static fn (\Closure $t) => $t(OfferingManager::class)->create(['identifier' => 'main']), 'POST', $m.'/offerings', '{"identifier":"main"}'];
        yield 'offerings get' => [static fn (\Closure $t) => $t(OfferingManager::class)->get('o1'), 'GET', $m.'/offerings/o1', null];
        yield 'offerings update' => [static fn (\Closure $t) => $t(OfferingManager::class)->update('o1', ['is_current' => true]), 'PATCH', $m.'/offerings/o1', '{"is_current":true}'];
        yield 'offerings delete' => [static fn (\Closure $t) => $t(OfferingManager::class)->delete('o1'), 'DELETE', $m.'/offerings/o1', null];
        yield 'experiments list' => [static fn (\Closure $t) => $t(ExperimentManager::class)->list(), 'GET', $m.'/experiments', null];
        yield 'experiments create' => [static fn (\Closure $t) => $t(ExperimentManager::class)->create(['name' => 'x', 'control_offering_id' => 'a', 'treatment_offering_id' => 'b']), 'POST', $m.'/experiments', '{"name":"x","control_offering_id":"a","treatment_offering_id":"b"}'];
        yield 'experiments get' => [static fn (\Closure $t) => $t(ExperimentManager::class)->get('x1'), 'GET', $m.'/experiments/x1', null];
        yield 'experiments stop' => [static fn (\Closure $t) => $t(ExperimentManager::class)->stop('x1'), 'POST', $m.'/experiments/x1/stop', null];
        yield 'integrations list' => [static fn (\Closure $t) => $t(IntegrationManager::class)->list(), 'GET', $m.'/integrations', null];
        yield 'integrations put' => [static fn (\Closure $t) => $t(IntegrationManager::class)->put('slack', ['secret' => 's', 'enabled' => true]), 'PUT', $m.'/integrations/slack', '{"secret":"s","enabled":true}'];
        yield 'integrations delete' => [static fn (\Closure $t) => $t(IntegrationManager::class)->delete('slack'), 'DELETE', $m.'/integrations/slack', null];
        yield 'integrations test' => [static fn (\Closure $t) => $t(IntegrationManager::class)->test('slack'), 'POST', $m.'/integrations/slack/test', null];
        yield 'metrics' => [static fn (\Closure $t) => $t(MetricsManager::class)->get('2026-01-01', '2026-01-31'), 'GET', $m.'/metrics?from=2026-01-01&to=2026-01-31', null];
        yield 'metrics unbounded' => [static fn (\Closure $t) => $t(MetricsManager::class)->get(), 'GET', $m.'/metrics', null];
        yield 'renewal prompts' => [static fn (\Closure $t) => $t(SubscriptionManager::class)->listRenewalPrompts('s1'), 'GET', $m.'/subscriptions/renewal_prompts?subscription_id=s1', null];
        yield 'send renewal prompt' => [static fn (\Closure $t) => $t(SubscriptionManager::class)->sendRenewalPrompt('s1'), 'POST', $m.'/subscriptions/s1/renewal_prompt', null];
    }

    /** @param Closure(class-string<BaseManager>): mixed $call */
    #[DataProvider('calls')]
    public function testAccessCallsUseDocumentedRoutes(\Closure $call, string $method, string $path, string|null $body): void
    {
        $this->expectRequest($method, $path);
        if (null === $body) {
            $this->body->expects($this->never())->method('write');
        } else {
            $this->body->expects($this->once())->method('write')->with($body);
        }
        $this->respondWith([]);

        $call(fn (string $class): BaseManager => new $class($this->config, $this->httpClient, $this->requestFactory, $this->auth));
    }

    public function testInitiateRequestCarriesAccessFields(): void
    {
        $request = new InitiatePaymentRequest(1.0, 'USD', 'stripe', 'm', 'https://e.com', productId: 'p', externalCustomerId: 'c', presentedOfferingId: 'o');

        $this->assertSame('p', $request->toArray()['productId']);
        $this->assertSame('c', $request->toArray()['externalCustomerId']);
        $this->assertSame('o', $request->toArray()['presentedOfferingId']);
        $this->assertArrayNotHasKey('productId', (new InitiatePaymentRequest(1.0, 'USD', 'stripe', 'm', 'https://e.com'))->toArray());
    }

    public function testAccessWebhookEventsAreParsed(): void
    {
        $purchase = new WebhookEvent(['id' => '1', 'type' => 'purchase.completed', 'source' => 'payment', 'timestamp' => 't', 'data' => ['product_id' => 'p', 'entitlement_ids' => ['pro'], 'external_customer_id' => 'c']]);
        $this->assertInstanceOf(EntitlementEventData::class, $purchase->getEntitlementData());
        $this->assertSame(['pro'], $purchase->getEntitlementData()->entitlementIds);

        $granted = new WebhookEvent(['id' => '2', 'type' => 'entitlement.granted', 'source' => 'entitlement', 'timestamp' => 't', 'data' => ['external_customer_id' => 'c']]);
        $this->assertSame('c', $granted->getEntitlementData()?->externalCustomerId);

        $moved = new WebhookEvent(['id' => '3', 'type' => 'customer.transferred', 'source' => 'customer', 'timestamp' => 't', 'data' => ['transferred_from' => ['a'], 'transferred_to' => ['b']]]);
        $this->assertInstanceOf(CustomerEventData::class, $moved->getCustomerData());
        $this->assertSame(['b'], $moved->getCustomerData()->transferredTo);

        $issue = new WebhookEvent(['id' => '4', 'type' => 'subscription.billing_issue', 'source' => 'subscription', 'timestamp' => 't', 'data' => ['subscription_id' => 's', 'external_customer_id' => 'ext1', 'product_id' => 'p', 'entitlement_ids' => ['pro'], 'grace_period_expiration_at' => 'g']]);
        $this->assertSame('g', $issue->getSubscriptionData()?->gracePeriodExpirationAt);
        $this->assertSame('ext1', $issue->getSubscriptionData()?->externalCustomerId);
        $this->assertSame(['pro'], $issue->getSubscriptionData()->entitlementIds);
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

    /** @param array<array-key, mixed> $data */
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
