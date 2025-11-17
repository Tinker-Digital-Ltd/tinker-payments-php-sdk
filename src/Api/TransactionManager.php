<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\DTO\InitiatePaymentRequest;
use Tinker\Model\DTO\QueryPaymentRequest;
use Tinker\Model\Transaction;

class TransactionManager extends BaseManager
{
    /**
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function initiate(InitiatePaymentRequest $request): Transaction
    {
        $payload = $request->toArray();
        $response = $this->request('POST', Endpoints::PAYMENT_INITIATE_PATH, $payload);

        return new Transaction($response);
    }

    /**
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function query(QueryPaymentRequest $request): Transaction
    {
        $payload = $request->toArray();
        $response = $this->request('POST', Endpoints::PAYMENT_QUERY_PATH, $payload);

        return new Transaction($response);
    }
}
