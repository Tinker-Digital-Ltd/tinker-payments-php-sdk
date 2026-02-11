<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\DTO\CreateSubscriptionPlanRequest;
use Tinker\Model\DTO\CreateSubscriptionRequest;

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
}
