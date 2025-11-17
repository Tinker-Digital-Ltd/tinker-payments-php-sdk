<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\InvalidPayloadException;

final class InvalidPayloadExceptionTest extends TestCase
{
    public function testInvalidPayloadExceptionIsThrowable(): void
    {
        $exception = new InvalidPayloadException('Invalid payload');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Invalid payload', $exception->getMessage());
        $this->assertSame(ExceptionCode::INVALID_PAYLOAD, $exception->getCode());
    }
}
