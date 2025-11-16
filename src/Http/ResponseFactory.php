<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    private readonly StreamFactory $streamFactory;

    public function __construct()
    {
        $this->streamFactory = new StreamFactory();
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, $reasonPhrase, $this->streamFactory);
    }
}
