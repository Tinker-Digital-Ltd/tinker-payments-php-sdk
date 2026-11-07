<?php

declare(strict_types=1);

namespace Tinker\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORISED = 'authorised';
    case SUCCESS = 'success';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case BLOCKED = 'blocked';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case EXPIRED = 'expired';
    case REVERSED = 'reversed';

    public static function parse(string $status): self|string
    {
        return self::tryFrom('completed' === $status ? 'success' : $status) ?? $status;
    }

    public static function text(self|string $status): string
    {
        return $status instanceof self ? $status->value : $status;
    }
}
