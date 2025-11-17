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

        $this->assertSame('https://payments.tinker.co.ke/api/', $config->getBaseUrl());
    }
}
