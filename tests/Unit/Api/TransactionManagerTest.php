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
use Tinker\Config\Configuration;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;
use Tinker\Model\Transaction;

final class TransactionManagerTest extends TestCase
{
    private Configuration $config;
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $requestFactory;
    private TransactionManager $transactionManager;

    protected function setUp(): void
    {
        $this->config = new Configuration('pk_test_123', 'sk_test_456');
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->transactionManager = new TransactionManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory
        );
    }

    public function testInitiateCreatesTransaction(): void
    {
        $transactionData = [
            'amount' => 1000.00,
            'currency' => 'KES',
        ];

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturn(null);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode(['id' => 'txn_123', 'status' => 'pending']));

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $this->stringContains('/transactions'))
            ->willReturn($request);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $transaction = $this->transactionManager->initiate($transactionData);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame('txn_123', $transaction->id);
    }

    public function testInitiateThrowsApiExceptionOnError(): void
    {
        $transactionData = ['amount' => 1000.00];

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);
        $bodyStream->method('write')->willReturn(0);
        $bodyStream->method('rewind')->willReturn(null);

        $response->method('getStatusCode')->willReturn(400);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode(['message' => 'Invalid amount']));

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->httpClient->method('sendRequest')->willReturn($response);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid amount');

        $this->transactionManager->initiate($transactionData);
    }

    public function testQueryFetchesTransaction(): void
    {
        $transactionId = 'txn_123';

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode(['id' => $transactionId, 'status' => 'completed']));

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('GET', $this->stringContains("/transactions/{$transactionId}"))
            ->willReturn($request);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $transaction = $this->transactionManager->query($transactionId);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame($transactionId, $transaction->id);
    }

    public function testQueryThrowsNetworkExceptionOnNetworkError(): void
    {
        $transactionId = 'txn_123';

        $request = $this->createMock(RequestInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->httpClient
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('Network error'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to communicate with Tinker API: Network error');

        $this->transactionManager->query($transactionId);
    }

    public function testListReturnsArrayOfTransactions(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'data' => [
                ['id' => 'txn_1', 'status' => 'pending'],
                ['id' => 'txn_2', 'status' => 'completed'],
            ],
        ]));

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->httpClient->method('sendRequest')->willReturn($response);

        $transactions = $this->transactionManager->list();

        $this->assertIsArray($transactions);
        $this->assertCount(2, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertInstanceOf(Transaction::class, $transactions[1]);
    }
}

