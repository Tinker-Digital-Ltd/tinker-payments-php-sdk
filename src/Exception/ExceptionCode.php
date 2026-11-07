<?php

declare(strict_types=1);

namespace Tinker\Exception;

final class ExceptionCode
{
    public const API_ERROR = 1000;
    public const NETWORK_ERROR = 2000;
    public const AUTHENTICATION_ERROR = 3000;
    public const INVALID_PAYLOAD = 4000;
    public const WEBHOOK_ERROR = 5000;
    public const STREAM_ERROR = 6000;
    public const CLIENT_ERROR = 7000;
}
