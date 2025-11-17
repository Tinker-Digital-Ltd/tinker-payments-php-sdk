<?php

declare(strict_types=1);

namespace Tinker;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Api\TransactionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Http\CurlClient;
use Tinker\Http\RequestFactory as CurlRequestFactory;
use Tinker\Webhook\WebhookHandler;

class TinkerPayments
{
    private readonly Configuration $config;
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly AuthenticationManager $authManager;
    private TransactionManager|null $transactions = null;

    public function __construct(
        string $apiPublicKey,
        string $apiSecretKey,
        ClientInterface|null $httpClient = null,
        RequestFactoryInterface|null $requestFactory = null,
    ) {
        $this->config = new Configuration($apiPublicKey, $apiSecretKey);
        $this->httpClient = $httpClient ?? new CurlClient();
        $this->requestFactory = $requestFactory ?? new CurlRequestFactory();
        $this->authManager = new AuthenticationManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
        );
    }

    public function transactions(): TransactionManager
    {
        return $this->transactions ??= new TransactionManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function webhooks(): WebhookHandler
    {
        return new WebhookHandler();
    }
}
