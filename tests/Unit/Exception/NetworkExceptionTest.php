<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\NetworkException;

final class NetworkExceptionTest extends TestCase
{
    public function testNetworkExceptionIsThrowable(): void
    {
        $exception = new NetworkException('Network error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Network error', $exception->getMessage());
    }
}

