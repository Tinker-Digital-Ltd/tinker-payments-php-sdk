<?php

declare(strict_types=1);

namespace Tinker\Tests\Unit\Auth;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Tinker\Auth\AuthenticationManager;
use Tinker\Config\Configuration;
use Tinker\Config\Endpoints;
use Tinker\Exception\ApiException;
use Tinker\Exception\NetworkException;

final class AuthenticationManagerTest extends TestCase
{
    private Configuration $config;
    private ClientInterface&MockObject $httpClient;
    private RequestFactoryInterface&MockObject $requestFactory;
    private StreamFactoryInterface&MockObject $streamFactory;
    private AuthenticationManager $authManager;

    protected function setUp(): void
    {
        $this->config = new Configuration('pk_test_123', 'sk_test_456');
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->authManager = new AuthenticationManager(
            $this->config,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
        );
    }

    public function testGetTokenFetchesTokenFromApi(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'token' => 'test_token_123',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ]));

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', Endpoints::AUTH_TOKEN_URL)
            ->willReturn($request);

        $this->streamFactory
            ->expects($this->once())
            ->method('createStream')
            ->willReturn($bodyStream);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $token = $this->authManager->getToken();

        $this->assertSame('test_token_123', $token);
    }

    public function testGetTokenReturnsCachedTokenWhenValid(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);

        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'token' => 'test_token_123',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ]));

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->streamFactory->method('createStream')->willReturn($bodyStream);

        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);

        $token1 = $this->authManager->getToken();
        $token2 = $this->authManager->getToken();

        $this->assertSame('test_token_123', $token1);
        $this->assertSame('test_token_123', $token2);
        $this->assertSame($token1, $token2);
    }

    public function testGetTokenThrowsApiExceptionOnError(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);

        $response->method('getStatusCode')->willReturn(401);
        $response->method('getBody')->willReturn($stream);
        $stream->method('__toString')->willReturn(json_encode([
            'message' => 'Invalid credentials',
        ]));

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->streamFactory->method('createStream')->willReturn($bodyStream);
        $this->httpClient->method('sendRequest')->willReturn($response);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authManager->getToken();
    }

    public function testGetTokenThrowsNetworkExceptionOnNetworkError(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $bodyStream = $this->createMock(StreamInterface::class);

        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();
        $request->method('getBody')->willReturn($bodyStream);

        $this->requestFactory->method('createRequest')->willReturn($request);
        $this->streamFactory->method('createStream')->willReturn($bodyStream);
        $this->httpClient
            ->method('sendRequest')
            ->willThrowException(new \RuntimeException('Network error'));

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to authenticate: Network error');

        $this->authManager->getToken();
    }
}
