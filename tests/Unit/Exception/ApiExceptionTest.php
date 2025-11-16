<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\ApiException;

final class ApiExceptionTest extends TestCase
{
    public function testApiExceptionIsThrowable(): void
    {
        $exception = new ApiException('Test message', 400);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }
}

