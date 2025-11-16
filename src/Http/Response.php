<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\ResponseInterface;

final class Response implements ResponseInterface
{
    use MessageTrait;
    private string $reasonPhrase;

    public function __construct(private int $statusCode = 200, string $reasonPhrase = '')
    {
        $this->reasonPhrase = '' !== $reasonPhrase ? $reasonPhrase : $this->getDefaultReasonPhrase($this->statusCode);
        $this->protocol = '1.1';
        $this->headers = [];
        $this->headerNames = [];
        $this->body = $this->streamFactory->createStream();
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = '' !== $reasonPhrase ? $reasonPhrase : $this->getDefaultReasonPhrase($code);

        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    private function getDefaultReasonPhrase(int $statusCode): string
    {
        $phrases = [
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        return $phrases[$statusCode] ?? '';
    }
}
