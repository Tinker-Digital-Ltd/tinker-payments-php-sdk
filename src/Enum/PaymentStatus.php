<?php

declare(strict_types=1);

namespace Tinker\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
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
