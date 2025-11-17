<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Enum\Gateway;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\Transaction;

class TransactionManager extends BaseManager
{
    /**
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function initiate(array $transactionData): Transaction
    {
        $payload = $this->buildInitiatePayload($transactionData);
        $response = $this->request('POST', Endpoints::PAYMENT_INITIATE_PATH, $payload);

        return new Transaction($response);
    }

    private function buildInitiatePayload(array $data): array
    {
        $gateway = $data['gateway'] instanceof Gateway
            ? $data['gateway']->value
            : $data['gateway'];

        $payload = [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'gateway' => $gateway,
            'merchantReference' => $data['merchantReference'],
            'callbackUrl' => $data['callbackUrl'],
        ];

        if (isset($data['customerPhone'])) {
            $payload['customerPhone'] = $data['customerPhone'];
        }

        if (isset($data['customerEmail'])) {
            $payload['customerEmail'] = $data['customerEmail'];
        }

        if (isset($data['transactionDesc'])) {
            $payload['transactionDesc'] = $data['transactionDesc'];
        }

        if (isset($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        return $payload;
    }

    /**
     * @throws NetworkException
     * @throws ApiException
     * @throws ClientExceptionInterface
     */
    public function query(string $paymentReference, string|Gateway $gateway): Transaction
    {
        $gatewayValue = $gateway instanceof Gateway ? $gateway->value : $gateway;
        $payload = [
            'payment_reference' => $paymentReference,
            'gateway' => $gatewayValue,
        ];
        $response = $this->request('POST', Endpoints::PAYMENT_QUERY_PATH, $payload);

        return new Transaction($response);
    }
}
