<?php

declare(strict_types=1);

namespace Tinker\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
}
