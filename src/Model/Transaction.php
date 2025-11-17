<?php

declare(strict_types=1);

namespace Tinker\Model;

use Tinker\Enum\PaymentStatus;
use Tinker\Model\DTO\CallbackData;
use Tinker\Model\DTO\InitiationData;
use Tinker\Model\DTO\QueryData;

class Transaction
{
    private readonly InitiationData|null $initiationData;
    private readonly QueryData|null $queryData;
    private readonly CallbackData|null $callbackData;
    public readonly PaymentStatus $status;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        // Detect which type of data we have
        if (isset($data['payment_reference']) && !isset($data['id'])) {
            // Initiation response
            $this->initiationData = new InitiationData($data);
            $this->queryData = null;
            $this->callbackData = null;
            $this->status = $this->initiationData->status;
        } elseif (isset($data['id']) && isset($data['reference'])) {
            // Query or callback response (same structure)
            $this->initiationData = null;
            $this->queryData = new QueryData($data);
            $this->callbackData = new CallbackData($data);
            $this->status = $this->queryData->status;
        } else {
            // Fallback for edge cases
            $this->initiationData = null;
            $this->queryData = null;
            $this->callbackData = null;
            $statusValue = $data['status'] ?? 'pending';
            $this->status = PaymentStatus::from($statusValue);
        }
    }

    public function getInitiationData(): InitiationData|null
    {
        return $this->initiationData;
    }

    public function getQueryData(): QueryData|null
    {
        return $this->queryData;
    }

    public function getCallbackData(): CallbackData|null
    {
        return $this->callbackData;
    }

    public function isSuccessful(): bool
    {
        return PaymentStatus::SUCCESS === $this->status;
    }

    public function isPending(): bool
    {
        return PaymentStatus::PENDING === $this->status;
    }

    public function isCancelled(): bool
    {
        return PaymentStatus::CANCELLED === $this->status;
    }

    public function isFailed(): bool
    {
        return PaymentStatus::FAILED === $this->status;
    }
}
