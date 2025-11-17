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
        $this->baseUrl = Endpoints::API_BASE_URL.'/';
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
