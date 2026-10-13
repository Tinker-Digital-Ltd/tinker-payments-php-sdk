<?php

declare(strict_types=1);

namespace Tinker\Exception;

class ApiException extends \Exception
{
    public function __construct(
        string $message,
        int $code = ExceptionCode::API_ERROR,
        \Throwable|null $previous = null,
        public readonly int|null $httpStatus = null,
        public readonly string|null $errorCode = null,
        public readonly string|null $providerCode = null,
        public readonly string|null $requestId = null,
        public readonly string|null $retryAfter = null,
        // Only an explicit not_applied confirms no mutation.
        public readonly string|null $outcome = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
