<?php

declare(strict_types=1);

namespace Tinker\Exception;

class StreamException extends \Exception
{
    public function __construct(string $message, int $code = ExceptionCode::STREAM_ERROR, \Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
