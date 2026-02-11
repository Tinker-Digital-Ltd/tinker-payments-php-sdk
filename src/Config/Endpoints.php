<?php

declare(strict_types=1);

namespace Tinker\Config;

final class Endpoints
{
    public const string PRODUCTION_BASE_URL = 'https://api.tinkerpayments.com';
    public const string SANDBOX_BASE_URL = 'https://sandbox-api.tinkerpayments.com';
    public const string API_VERSION_PATH = '/v1';
    public const string AUTH_TOKEN_PATH = '/auth/token';
    public const string PAYMENT_INITIATE_PATH = '/merchant/payment/initiate';
    public const string PAYMENT_QUERY_PATH = '/merchant/payment/query';
    public const string SUBSCRIPTION_BASE_PATH = '/merchant/subscriptions';
    public const string SUBSCRIPTION_PLANS_PATH = '/merchant/subscriptions/plans';
}
