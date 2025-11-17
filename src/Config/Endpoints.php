<?php

declare(strict_types=1);

namespace Tinker\Config;

final class Endpoints
{
    public const string BASE_URL = 'https://payments.tinker.co.ke';
    public const string API_BASE_URL = self::BASE_URL.'/api';
    public const string AUTH_TOKEN_URL = self::BASE_URL.'/auth/token';
    public const string PAYMENT_INITIATE_PATH = '/payment/initiate';
    public const string PAYMENT_QUERY_PATH = '/payment/query';
}
