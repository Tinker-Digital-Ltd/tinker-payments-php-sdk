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
        return $this->request('GET', $this->customerPath($externalCustomerId).'/entitlements/'.rawurlencode($key));
    }

    private function customerPath(string $externalCustomerId): string
    {
        return Endpoints::CUSTOMERS_PATH.'/'.rawurlencode($externalCustomerId);
    }
}
