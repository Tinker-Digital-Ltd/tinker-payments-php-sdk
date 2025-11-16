<?php

declare(strict_types=1);

namespace Tinker\Api;

use Tinker\Model\Transaction;

class TransactionManager extends BaseManager
{
    public function initiate(array $transactionData): Transaction
    {
        $response = $this->request('POST', '/transactions', $transactionData);

        return new Transaction($response);
    }

    public function query(string $transactionId): Transaction
    {
        $response = $this->request('GET', "/transactions/{$transactionId}");

        return new Transaction($response);
    }

    public function list(array $params = []): array
    {
        $response = $this->request('GET', '/transactions', $params);

        return array_map(fn ($item) => new Transaction($item), $response['data']);
    }
}
