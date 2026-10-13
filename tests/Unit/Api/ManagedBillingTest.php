<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Tinker\Api\SubscriptionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Http\RequestFactory;
use Tinker\Http\Response;

final class ManagedBillingTest extends TestCase
{
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
