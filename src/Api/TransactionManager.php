<?php

declare(strict_types=1);

namespace Tinker\Api;

use Psr\Http\Client\ClientExceptionInterface;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\CancellationResult;
use Tinker\Model\CheckoutCancellation;
use Tinker\Model\DTO\CancellationRequest;
use Tinker\Model\DTO\InitiatePaymentRequest;
use Tinker\Model\DTO\QueryPaymentRequest;
use Tinker\Model\DTO\RefundRequest;
use Tinker\Model\Refund;
use Tinker\Model\RefundResult;
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

    public function refund(string $paymentId, RefundRequest $request, string $key): RefundResult
    {
        return new RefundResult($this->request('POST', $this->paymentPath($paymentId).'/refund', $request->toArray(), $this->operationHeaders($key)));
    }

    /** @return array<string, mixed> Refund summary including capability and history. */
    public function refunds(string $paymentId): array
    {
        return $this->request('GET', $this->paymentPath($paymentId).'/refunds');
    }

    public function getRefund(string $paymentId, string $refundId): Refund
    {
        return new Refund($this->request('GET', $this->paymentPath($paymentId).'/refunds/'.$this->resourceId($refundId)));
    }

    public function cancelCheckout(string $paymentId, CancellationRequest $request, string $key): CancellationResult
    {
        return new CancellationResult($this->request('POST', $this->paymentPath($paymentId).'/cancellations', $request->toArray(), $this->operationHeaders($key)));
    }

    /** @return array<string, mixed> Cancellation history and current capability. */
    public function cancellations(string $paymentId): array
    {
        return $this->request('GET', $this->paymentPath($paymentId).'/cancellations');
    }

    public function getCancellation(string $paymentId, string $requestId): CheckoutCancellation
    {
        return new CheckoutCancellation($this->request('GET', $this->paymentPath($paymentId).'/cancellations/'.$this->resourceId($requestId)));
    }
}
