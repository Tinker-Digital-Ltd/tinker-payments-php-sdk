<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class CustomerManager extends BaseManager
{
    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function getCustomer(string $externalCustomerId): array
    {
        return $this->request('GET', $this->customerPath($externalCustomerId));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function getEntitlement(string $externalCustomerId, string $key): array
    {
        return $this->request('GET', $this->entitlementPath($externalCustomerId, $key));
    }

    /**
     * @param array<string, mixed> $grant `duration` or `end_time`
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function grantPromotionalEntitlement(string $externalCustomerId, string $key, array $grant = []): array
    {
        return $this->request('POST', $this->entitlementPath($externalCustomerId, $key).'/promotional', $grant);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function revokePromotionalEntitlements(string $externalCustomerId, string $key): array
    {
        return $this->request('POST', $this->entitlementPath($externalCustomerId, $key).'/revoke_promotionals');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function alias(string $externalCustomerId, string $alias): array
    {
        return $this->request('POST', $this->customerPath($externalCustomerId).'/alias', ['alias' => $alias]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function transfer(string $externalCustomerId, string $to): array
    {
        return $this->request('POST', $this->customerPath($externalCustomerId).'/transfer', ['to' => $to]);
    }

    /**
     * @param array<string, string|null> $attributes
     *
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function setAttributes(string $externalCustomerId, array $attributes): array
    {
        return $this->request('POST', $this->customerPath($externalCustomerId).'/attributes', ['attributes' => $attributes]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function getOfferings(string $externalCustomerId): array
    {
        return $this->request('GET', $this->customerPath($externalCustomerId).'/offerings');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function createToken(string $externalCustomerId, int|null $expiresIn = null): array
    {
        return $this->request('POST', $this->customerPath($externalCustomerId).'/token', null === $expiresIn ? [] : ['expires_in' => $expiresIn]);
    }

    private function customerPath(string $externalCustomerId): string
    {
        return Endpoints::CUSTOMERS_PATH.'/'.rawurlencode($externalCustomerId);
    }

    private function entitlementPath(string $externalCustomerId, string $key): string
    {
        return $this->customerPath($externalCustomerId).'/entitlements/'.rawurlencode($key);
    }
}
