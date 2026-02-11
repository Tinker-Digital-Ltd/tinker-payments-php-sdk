<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Tinker\Config\Configuration;

final class ConfigurationTest extends TestCase
{
    public function testConfigurationStoresApiKeys(): void
    {
        $config = new Configuration('pk_test_123', 'sk_test_456');

        $this->assertSame('pk_test_123', $config->getApiPublicKey());
        $this->assertSame('sk_test_456', $config->getApiSecretKey());
        $this->assertSame('sk_test_456', $config->getApiKey());
    }

    public function testConfigurationReturnsBaseUrl(): void
    {
        $config = new Configuration('pk_test_123', 'sk_test_456');

        $this->assertSame('https://sandbox-api.tinkerpayments.com/v1/', $config->getBaseUrl());
        $this->assertSame('https://sandbox-api.tinkerpayments.com/v1/auth/token', $config->getAuthUrl());
    }

    public function testConfigurationUsesProductionForLiveKeys(): void
    {
        $config = new Configuration('pk_live_123', 'sk_live_456');

        $this->assertSame('https://api.tinkerpayments.com/v1/', $config->getBaseUrl());
        $this->assertSame('https://api.tinkerpayments.com/v1/auth/token', $config->getAuthUrl());
    }

    public function testConfigurationSupportsExplicitBaseUrlOverride(): void
    {
        $config = new Configuration('pk_test_123', 'sk_test_456', 'http://localhost:8080');
        $configWithVersion = new Configuration('pk_test_123', 'sk_test_456', 'http://localhost:8080/v1');

        $this->assertSame('http://localhost:8080/v1/', $config->getBaseUrl());
        $this->assertSame('http://localhost:8080/v1/auth/token', $config->getAuthUrl());
        $this->assertSame('http://localhost:8080/v1/', $configWithVersion->getBaseUrl());
    }
}
