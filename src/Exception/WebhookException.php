<?php

declare(strict_types=1);

namespace Tinker\Exception;

class WebhookException extends \Exception
{
    public function __construct(string $message, int $code = ExceptionCode::WEBHOOK_ERROR, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
