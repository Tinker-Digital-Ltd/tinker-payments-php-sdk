<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tinker\Exception\ClientException;
use Tinker\Exception\ExceptionCode;

final class CurlClient implements ClientInterface
{
    private readonly StreamFactory $streamFactory;

    public function __construct()
    {
        $this->streamFactory = new StreamFactory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (!extension_loaded('curl')) {
            throw new ClientException('cURL extension is required but not loaded', ExceptionCode::CLIENT_ERROR);
        }

        $ch = curl_init();
        if (false === $ch) {
            throw new ClientException('Failed to initialize cURL', ExceptionCode::CLIENT_ERROR);
        }

        $url = (string) $request->getUri();
        $method = $request->getMethod();
        $httpVersion = $this->getCurlHttpVersion($request->getProtocolVersion());

        /** @var array<int, mixed> $options */
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => $httpVersion,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        curl_setopt_array($ch, $options);

        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name.': '.$value;
            }
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $body = (string) $request->getBody();
        if ('' !== $body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (0 !== $errno) {
            $exception = new class($error, $request) extends \Exception implements NetworkExceptionInterface {
                public function __construct(string $message, private readonly RequestInterface $request)
                {
                    parent::__construct($message);
                }

                public function getRequest(): RequestInterface
                {
                    return $this->request;
                }
            };
            throw $exception;
        }

        if (false === $response || true === $response) {
            $exception = new class('cURL request failed', $request) extends \Exception implements NetworkExceptionInterface {
                public function __construct(string $message, private readonly RequestInterface $request)
                {
                    parent::__construct($message);
                }

                public function getRequest(): RequestInterface
                {
                    return $this->request;
                }
            };
            throw $exception;
        }

        [$headerString, $bodyString] = $this->splitResponse($response);

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse($statusCode ?: 200);

        foreach ($this->parseHeaders($headerString) as $name => $values) {
            foreach ($values as $value) {
                $response = $response->withAddedHeader($name, $value);
            }
        }

        $bodyStream = $this->streamFactory->createStream($bodyString);

        return $response->withBody($bodyStream);
    }

    private function getCurlHttpVersion(string $protocolVersion): int
    {
        return match ($protocolVersion) {
            '1.0' => CURL_HTTP_VERSION_1_0,
            '1.1' => CURL_HTTP_VERSION_1_1,
            '2.0' => CURL_HTTP_VERSION_2_0,
            default => CURL_HTTP_VERSION_1_1,
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitResponse(string $response): array
    {
        $headerSize = 0;
        $headerString = '';
        $bodyString = '';

        if (str_contains($response, "\r\n\r\n")) {
            [$headerString, $bodyString] = explode("\r\n\r\n", $response, 2);
            $headerSize = strlen($headerString) + 4;
        } elseif (str_contains($response, "\n\n")) {
            [$headerString, $bodyString] = explode("\n\n", $response, 2);
            $headerSize = strlen($headerString) + 2;
        } else {
            $bodyString = $response;
        }

        return [$headerString, $bodyString];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!isset($headers[$name])) {
                $headers[$name] = [];
            }

            $headers[$name][] = $value;
        }

        return $headers;
    }
}
