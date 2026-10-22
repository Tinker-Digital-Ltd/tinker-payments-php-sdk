<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\DTO\CreateSubscriptionPlanRequest;
use Tinker\Model\DTO\CreateSubscriptionRequest;
use Tinker\Model\DTO\ManagedCheckout;

class SubscriptionManager extends BaseManager
{
    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function createPlan(CreateSubscriptionPlanRequest $request): array
    {
        return $this->request('POST', Endpoints::SUBSCRIPTION_PLANS_PATH, $request->toArray());
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function listPlans(): array
    {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->request('GET', Endpoints::SUBSCRIPTION_PLANS_PATH);

        return $result;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function create(CreateSubscriptionRequest $request): array
    {
        return $this->request('POST', Endpoints::SUBSCRIPTION_BASE_PATH, $request->toArray());
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function list(string|null $planId = null, string|null $externalCustomerId = null): array
    {
        $params = array_filter([
            'plan_id' => $planId,
            'external_customer_id' => $externalCustomerId,
        ], static fn (mixed $value): bool => null !== $value && '' !== trim((string) $value));

        $endpoint = Endpoints::SUBSCRIPTION_BASE_PATH;
        if ([] !== $params) {
            $endpoint .= '?'.http_build_query($params);
        }

        /** @var array<int, array<string, mixed>> $result */
        $result = $this->request('GET', $endpoint);

        return $result;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function cancel(string $subscriptionId): array
    {
        return $this->request(
            'POST',
            Endpoints::SUBSCRIPTION_BASE_PATH.'/'.urlencode($subscriptionId).'/cancel',
        );
    }

    /** @return array<string, mixed> */
    public function checkout(ManagedCheckout $request): array
    {
        return $this->request('POST', 'merchant/billing/checkout', $request->toArray());
    }

    /** @return array<string, mixed> */
    public function billingResource(string $id): array
    {
        return $this->request('GET', $this->billingResourcePath($id));
    }

    /** @return array<string, mixed> */
    public function changeQuantity(string $id, int $quantity, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/quantity', ['quantity' => $quantity, 'idempotency_key' => $key]);
    }

    /** @return array<string, mixed> */
    public function scheduleQuantity(string $id, int $quantity, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/schedule', ['quantity' => $quantity, 'idempotency_key' => $key]);
    }

    /** @return array<string, mixed> */
    public function clearScheduledQuantity(string $id, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/unschedule', ['idempotency_key' => $key]);
    }

    /** @return array<string, mixed> */
    public function setCancelAtRenewal(string $id, bool $cancel, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/cancel', ['cancel' => $cancel, 'idempotency_key' => $key]);
    }

    /** @return array<string, mixed> */
    public function setTrialEnd(string $id, \DateTimeInterface $end, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/trial-end', ['trial_end' => $end->getTimestamp(), 'idempotency_key' => $key]);
    }

    /** @return array<string, mixed> */
    public function endTrial(string $id, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/end-trial', ['idempotency_key' => $key]);
    }

    /**
     * Recover a durable result without replaying a provider charge.
     *
     * @return array<string, mixed>
     */
    public function billingOperation(string $key): array
    {
        return $this->request('GET', 'merchant/billing/operations/'.rawurlencode($key));
    }

    /** @return array<string, mixed> */
    public function paymentMethodPortal(string $id, string $returnUrl, string $key): array
    {
        return $this->request('POST', $this->billingResourcePath($id).'/payment-method', ['return_url' => $returnUrl, 'idempotency_key' => $key]);
    }

    /** @return array<string, mixed> */
    public function billingPaymentHistory(string $id, string $cursor = '', int $snapshot = 0): array
    {
        $params = [];
        if ('' !== $cursor) {
            $params['starting_after'] = $cursor;
        }
        if ($snapshot > 0) {
            $params['snapshot'] = $snapshot;
        }
        $endpoint = $this->billingResourcePath($id).'/payment-history';
        if ([] !== $params) {
            $endpoint .= '?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $this->request('GET', $endpoint);
    }

    private function billingResourcePath(string $id): string
    {
        return 'merchant/billing/resources/'.rawurlencode($id);
    }
}
