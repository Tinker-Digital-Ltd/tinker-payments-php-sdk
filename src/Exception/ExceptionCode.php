<?php

declare(strict_types=1);

namespace Tinker\Exception;

final class ExceptionCode
{
    public const int API_ERROR = 1000;
    public const int NETWORK_ERROR = 2000;
    public const int AUTHENTICATION_ERROR = 3000;
    public const int INVALID_PAYLOAD = 4000;
    public const int WEBHOOK_ERROR = 5000;
    public const int STREAM_ERROR = 6000;
    public const int CLIENT_ERROR = 7000;
}
