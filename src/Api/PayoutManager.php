<?php

declare(strict_types=1);

namespace Tinker\Api;

use Tinker\Model\DTO\PayoutRequest;
use Tinker\Model\Payout;
use Tinker\Model\PayoutResult;

/** Merchant withdrawals to existing verified payout accounts. */
final class PayoutManager extends BaseManager
{
    /** @return array<array<string, mixed>> Masked payout methods. */
    public function methods(): array
    {
        return array_values($this->request('GET', '/merchant/payout-methods'));
    }

    /** @return array<string, mixed> Gross amount, fee, net amount and timing. */
    public function quote(string $payoutMethodId, float $amount, string|null $currency = null): array
    {
        $query = ['payout_method_id' => $payoutMethodId, 'amount' => $amount];
        if (null !== $currency) {
            $query['currency'] = $currency;
        }

        return $this->request('GET', '/merchant/payouts/quote?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    public function requestPayout(PayoutRequest $request, string $key): PayoutResult
    {
        return new PayoutResult($this->request('POST', '/merchant/payouts', $request->toArray(), $this->operationHeaders($key)));
    }

    /**
     * @param array{page?: int, per_page?: int, status?: string, currency?: string} $filters
     *
     * @return array<string, mixed> payouts and pagination
     */
    public function list(array $filters = []): array
    {
        $query = http_build_query($filters, '', '&', PHP_QUERY_RFC3986);

        return $this->request('GET', '/merchant/payouts'.('' === $query ? '' : '?'.$query));
    }

    public function get(string $id): Payout
    {
        return new Payout($this->request('GET', '/merchant/payouts/'.$this->resourceId($id)));
    }
}
