<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\NetworkException;

final class NetworkExceptionTest extends TestCase
{
    public function testNetworkExceptionIsThrowable(): void
    {
        $exception = new NetworkException('Network error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Network error', $exception->getMessage());
        $this->assertSame(ExceptionCode::NETWORK_ERROR, $exception->getCode());
    }

    public function testNetworkExceptionWithCustomCode(): void
    {
        $exception = new NetworkException('Network error', 6000);

        $this->assertSame('Network error', $exception->getMessage());
        $this->assertSame(6000, $exception->getCode());
    }
}
