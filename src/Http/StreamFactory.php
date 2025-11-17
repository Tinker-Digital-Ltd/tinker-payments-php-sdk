<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Tinker\Exception\ExceptionCode;
use Tinker\Exception\StreamException;

final class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = fopen($filename, $mode);
        if (false === $resource) {
            throw new StreamException("Unable to open file: {$filename}", ExceptionCode::STREAM_ERROR);
        }

        return new Stream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
