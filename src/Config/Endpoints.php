<?php

declare(strict_types=1);

namespace Tinker\Config;

final class Endpoints
{
    public const PRODUCTION_BASE_URL = 'https://api.tinkerpayments.com';
    public const SANDBOX_BASE_URL = 'https://sandbox-api.tinkerpayments.com';
    public const API_VERSION_PATH = '/v1';
    public const AUTH_TOKEN_PATH = '/auth/token';
    public const PAYMENT_INITIATE_PATH = '/merchant/payment/initiate';
    public const PAYMENT_QUERY_PATH = '/merchant/payment/query';
    public const PAYMENT_CAPABILITIES_PATH = '/merchant/payment/capabilities';
    public const SUBSCRIPTION_BASE_PATH = '/merchant/subscriptions';
    public const SUBSCRIPTION_PLANS_PATH = '/merchant/subscriptions/plans';
    public const SUBSCRIPTION_INVOICES_PATH = '/merchant/subscriptions/invoices';
}
