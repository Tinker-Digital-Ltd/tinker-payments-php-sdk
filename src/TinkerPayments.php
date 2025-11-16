<?php

declare(strict_types=1);

namespace Tinker;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tinker\Api\AccountManager;
use Tinker\Api\TransactionManager;
use Tinker\Api\WalletManager;
use Tinker\Config\Configuration;
use Tinker\Http\CurlClient;
use Tinker\Http\RequestFactory as CurlRequestFactory;

class TinkerPayments
{
    private readonly Configuration $config;
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private TransactionManager|null $transactions = null;
    private WalletManager|null $wallets = null;
    private AccountManager|null $accounts = null;

    public function __construct(
        string $apiPublicKey,
        string $apiSecretKey,
        ClientInterface|null $httpClient = null,
        RequestFactoryInterface|null $requestFactory = null,
        string|null $environment = null,
    ) {
        $this->config = new Configuration($apiPublicKey, $apiSecretKey, $environment);
        $this->httpClient = $httpClient ?? new CurlClient();
        $this->requestFactory = $requestFactory ?? new CurlRequestFactory();
    }

    public function transactions(): TransactionManager
    {
        return $this->transactions ??= new TransactionManager($this->config, $this->httpClient, $this->requestFactory);
    }

    public function wallets(): WalletManager
    {
        return $this->wallets ??= new WalletManager($this->config, $this->httpClient, $this->requestFactory);
    }

    public function accounts(): AccountManager
    {
        return $this->accounts ??= new AccountManager($this->config, $this->httpClient, $this->requestFactory);
    }
}
