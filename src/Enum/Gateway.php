<?php

declare(strict_types=1);

namespace Tinker\Enum;

enum Gateway: string
{
    case MPESA = 'mpesa';
    case PAYSTACK = 'paystack';
    case STRIPE = 'stripe';
}
