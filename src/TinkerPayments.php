<?php

declare(strict_types=1);

namespace Tinker;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Api\CustomerManager;
use Tinker\Api\EntitlementManager;
use Tinker\Api\ExperimentManager;
use Tinker\Api\IntegrationManager;
use Tinker\Api\MetricsManager;
use Tinker\Api\OfferingManager;
use Tinker\Api\PayoutManager;
use Tinker\Api\ProductManager;
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
    private CustomerManager|null $customers = null;
    private ProductManager|null $products = null;
    private OfferingManager|null $offerings = null;
    private MetricsManager|null $metrics = null;
    private IntegrationManager|null $integrations = null;
    private ExperimentManager|null $experiments = null;
    private EntitlementManager|null $entitlements = null;
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

    public function customers(): CustomerManager
    {
        return $this->customers ??= new CustomerManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function entitlements(): EntitlementManager
    {
        return $this->entitlements ??= new EntitlementManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function experiments(): ExperimentManager
    {
        return $this->experiments ??= new ExperimentManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function integrations(): IntegrationManager
    {
        return $this->integrations ??= new IntegrationManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function metrics(): MetricsManager
    {
        return $this->metrics ??= new MetricsManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function offerings(): OfferingManager
    {
        return $this->offerings ??= new OfferingManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function products(): ProductManager
    {
        return $this->products ??= new ProductManager(
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
