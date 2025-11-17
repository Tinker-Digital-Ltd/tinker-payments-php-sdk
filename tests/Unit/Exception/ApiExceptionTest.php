<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\ApiException;
use Tinker\Exception\ExceptionCode;

final class ApiExceptionTest extends TestCase
{
    public function testApiExceptionIsThrowable(): void
    {
        $exception = new ApiException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(ExceptionCode::API_ERROR, $exception->getCode());
    }

    public function testApiExceptionWithCustomCode(): void
    {
        $exception = new ApiException('Test message', 5000);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(5000, $exception->getCode());
    }
}
