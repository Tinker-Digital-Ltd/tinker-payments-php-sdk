<?php

declare(strict_types=1);

namespace Tinker\Exception;

class ClientException extends \Exception
{
    public function __construct(string $message, int $code = ExceptionCode::CLIENT_ERROR, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
