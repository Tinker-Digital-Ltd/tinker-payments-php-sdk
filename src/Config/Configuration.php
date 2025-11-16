<?php

declare(strict_types=1);

namespace Tinker\Config;

class Configuration
{
    private readonly string $baseUrl;

    public function __construct(
        private readonly string $apiPublicKey,
        private readonly string $apiSecretKey,
    ) {
        $this->baseUrl = 'https://payments.tinker.co.ke/api/';
    }

    public function getApiPublicKey(): string
    {
        return $this->apiPublicKey;
    }

    public function getApiSecretKey(): string
    {
        return $this->apiSecretKey;
    }

    public function getApiKey(): string
    {
        return $this->apiSecretKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
