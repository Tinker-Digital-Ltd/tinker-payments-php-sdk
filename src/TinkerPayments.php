<?php

declare(strict_types=1);

namespace Tinker;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Api\PayoutManager;
use Tinker\Api\SubscriptionManager;
use Tinker\Api\TransactionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Http\CurlClient;
use Tinker\Http\RequestFactory as CurlRequestFactory;
use Tinker\Model\ApiMeta;
use Tinker\Webhook\WebhookHandler;

class TinkerPayments
{
    private readonly Configuration $config;
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly AuthenticationManager $authManager;
    private PayoutManager|null $payouts = null;
    private TransactionManager|null $transactions = null;
    private SubscriptionManager|null $subscriptions = null;
    private WebhookHandler|null $webhooks = null;

    public function __construct(
        string $apiPublicKey,
        string $apiSecretKey,
        ClientInterface|null $httpClient = null,
        RequestFactoryInterface|null $requestFactory = null,
        string|null $baseUrl = null,
    ) {
        $this->config = new Configuration($apiPublicKey, $apiSecretKey, $baseUrl);
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

    public function payouts(): PayoutManager
    {
        return $this->payouts ??= new PayoutManager($this->config, $this->httpClient, $this->requestFactory, $this->authManager);
    }

    public function webhooks(): WebhookHandler
    {
        return $this->webhooks ??= new WebhookHandler();
    }

    public function subscriptions(): SubscriptionManager
    {
        return $this->subscriptions ??= new SubscriptionManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function getLastAuthMeta(): ApiMeta|null
    {
        return $this->authManager->getLastMeta();
    }
}
