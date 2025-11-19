<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Api;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tinker\Api\TransactionManager;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Enum\Gateway;
use Tinker\Enum\PaymentStatus;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\DTO\InitiatePaymentRequest;
use Tinker\Model\DTO\QueryPaymentRequest;
use Tinker\Model\Transaction;

final class TransactionManagerTest extends TestCase
{
    private Configuration $config;
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $requestFactory;
    private AuthenticationManager&MockObject $authManager;
    private TransactionManager $transactionManager;

    protected function setUp(): void
    {
        $this->config = new Configuration('pk_test_123', 'sk_test_456');
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->authManager = $this->createMock(AuthenticationManager::class);
        $this->authManager->method('getToken')->willReturn('test_token_123');
        $this->transactionManager = new TransactionManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->authManager,
        );
    }

    public function testInitiateCreatesTransaction(): void
    {
        $initiateRequest = new InitiatePaymentRequest(
            amount: 100.00,
            currency: 'KES',
            gateway: 'mpesa',
            merchantReference: 'ORDER-12345',
            returnUrl: 'https://your-app.com/payment/return',
            customerPhone: '+254712345678',
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturnCallback(function (): void {});

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'payment_reference' => 'TXN-abc123xyz',
            'status' => 'pending',
            'authorization_url' => null,
        ]));

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/payment/initiate'))
            ->willReturn($request);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $transaction = $this->transactionManager->initiate($initiateRequest);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $initiationData = $transaction->getInitiationData();
        $this->assertNotNull($initiationData);
        $this->assertSame('TXN-abc123xyz', $initiationData->paymentReference);
        $this->assertSame(PaymentStatus::PENDING, $initiationData->status);
        $this->assertNull($initiationData->authorizationUrl);
        $this->assertSame(PaymentStatus::PENDING, $transaction->status);
    }

    public function testInitiateThrowsApiExceptionOnError(): void
    {
        $initiateRequest = new InitiatePaymentRequest(
            amount: 100.00,
            currency: 'KES',
            gateway: 'mpesa',
            merchantReference: 'ORDER-12345',
            returnUrl: 'https://your-app.com/payment/return',
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturnCallback(function (): void {});

        $response->method('getStatusCode')->willReturn(400);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode(['message' => 'Invalid amount']));

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->httpClient->method('sendRequest')->willReturn($response);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid amount');

        $this->transactionManager->initiate($initiateRequest);
    }

    public function testQueryFetchesTransaction(): void
    {
        $queryRequest = new QueryPaymentRequest(
            paymentReference: 'TXN-abc123xyz',
            gateway: 'mpesa',
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturnCallback(function (): void {});

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ]));

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/payment/query'))
            ->willReturn($request);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $transaction = $this->transactionManager->query($queryRequest);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $queryData = $transaction->getQueryData();
        $this->assertNotNull($queryData);
        $this->assertSame('pay_abc123', $queryData->id);
        $this->assertSame('TXN-abc123xyz', $queryData->reference);
        $this->assertSame(100.00, $queryData->amount);
        $this->assertSame('KES', $queryData->currency);
        $this->assertSame(PaymentStatus::SUCCESS, $queryData->status);
        $this->assertSame('mpesa', $queryData->channel);
        $this->assertSame('2024-01-15T10:30:00Z', $queryData->paidAt);
        $this->assertSame('2024-01-15T10:25:00Z', $queryData->createdAt);
        $this->assertSame(PaymentStatus::SUCCESS, $transaction->status);
    }

    public function testQueryThrowsNetworkExceptionOnNetworkError(): void
    {
        $queryRequest = new QueryPaymentRequest(
            paymentReference: 'TXN-abc123xyz',
            gateway: 'mpesa',
        );

        $request = $this->createMock(RequestInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturnCallback(function (): void {});

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->httpClient
            ->method('sendRequest')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to communicate with Tinker API: Network error');

        $this->transactionManager->query($queryRequest);
    }

    public function testQueryAcceptsGatewayEnum(): void
    {
        $queryRequest = new QueryPaymentRequest(
            paymentReference: 'TXN-abc123xyz',
            gateway: Gateway::MPESA,
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturnCallback(function (): void {});

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'id' => 'pay_abc123',
            'status' => 'success',
            'reference' => 'TXN-abc123xyz',
            'amount' => 100.00,
            'currency' => 'KES',
            'paid_at' => '2024-01-15T10:30:00Z',
            'created_at' => '2024-01-15T10:25:00Z',
            'channel' => 'mpesa',
        ]));

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->httpClient->method('sendRequest')->willReturn($response);

        $transaction = $this->transactionManager->query($queryRequest);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $queryData = $transaction->getQueryData();
        $this->assertNotNull($queryData);
        $this->assertSame('pay_abc123', $queryData->id);
    }
}
