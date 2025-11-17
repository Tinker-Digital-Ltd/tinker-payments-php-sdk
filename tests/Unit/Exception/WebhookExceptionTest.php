<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\WebhookException;

final class WebhookExceptionTest extends TestCase
{
    public function testWebhookExceptionIsThrowable(): void
    {
        $exception = new WebhookException('Webhook error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Webhook error', $exception->getMessage());
        $this->assertSame(ExceptionCode::WEBHOOK_ERROR, $exception->getCode());
    }
}
