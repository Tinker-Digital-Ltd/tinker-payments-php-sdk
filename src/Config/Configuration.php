<?php

declare(strict_types=1);

namespace Tinker\Config;

class Configuration
{
    private readonly string $baseUrl;
    private readonly string $authUrl;

    public function __construct(
        private readonly string $apiPublicKey,
        private readonly string $apiSecretKey,
        string|null $baseUrl = null,
    ) {
        $resolvedBaseUrl = $baseUrl;
        if (null === $resolvedBaseUrl || '' === trim($resolvedBaseUrl)) {
            $resolvedBaseUrl = $this->isSandboxCredentials()
                ? Endpoints::SANDBOX_BASE_URL
                : Endpoints::PRODUCTION_BASE_URL;
        }

        $resolvedBaseUrl = rtrim($resolvedBaseUrl, '/');

        // Allow passing either host or full API base (with /v1) as override.
        if (!str_ends_with($resolvedBaseUrl, Endpoints::API_VERSION_PATH)) {
            $resolvedBaseUrl .= Endpoints::API_VERSION_PATH;
        }

        $this->baseUrl = $resolvedBaseUrl.'/';
        $this->authUrl = $resolvedBaseUrl.Endpoints::AUTH_TOKEN_PATH;
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

    public function getAuthUrl(): string
    {
        return $this->authUrl;
    }

    private function isSandboxCredentials(): bool
    {
        return str_starts_with($this->apiPublicKey, 'pk_test_')
            || str_starts_with($this->apiSecretKey, 'sk_test_');
    }
}
