<?php

declare(strict_types=1);

namespace Tinker\Exception;

class InvalidPayloadException extends \Exception
{
    public function __construct(string $message, int $code = ExceptionCode::INVALID_PAYLOAD, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
