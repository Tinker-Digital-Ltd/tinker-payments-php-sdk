<?php

declare(strict_types=1);

namespace Tinker\Http;

use Psr\Http\Message\StreamInterface;
use Tinker\Exception\StreamException;

final class Stream implements StreamInterface
{
    /** @var resource|null */
    private $resource;
    private string|null $content = null;
    private bool $isResource;
    private int $position = 0;

    /**
     * @param resource|string $content
     */
    public function __construct($content = '')
    {
        if (is_resource($content)) {
            $this->resource = $content;
            $this->isResource = true;
        } else {
            $this->content = (string) $content;
            $this->isResource = false;
        }
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close(): void
    {
        if ($this->isResource && is_resource($this->resource)) {
            fclose($this->resource);
            $this->resource = null;
        }
        $this->content = null;
    }

    public function detach()
    {
        if ($this->isResource) {
            $resource = $this->resource;
            $this->resource = null;

            return $resource;
        }

        return null;
    }

    public function getSize(): int|null
    {
        if ($this->isResource && is_resource($this->resource)) {
            $stats = fstat($this->resource);

            return $stats ? $stats['size'] : null;
        }

        return null !== $this->content ? strlen($this->content) : null;
    }

    public function tell(): int
    {
        if ($this->isResource && is_resource($this->resource)) {
            $position = ftell($this->resource);
            if (false === $position) {
                throw new StreamException('Unable to determine stream position');
            }

            return $position;
        }

        return $this->position;
    }

    public function eof(): bool
    {
        if ($this->isResource && is_resource($this->resource)) {
            return feof($this->resource);
        }

        return $this->position >= strlen($this->content ?? '');
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->isResource && is_resource($this->resource)) {
            if (0 !== fseek($this->resource, $offset, $whence)) {
                throw new StreamException('Unable to seek stream');
            }
        } else {
            $length = strlen($this->content ?? '');
            $newPosition = match ($whence) {
                SEEK_SET => $offset,
                SEEK_CUR => $this->position + $offset,
                SEEK_END => $length + $offset,
                default => throw new StreamException('Invalid whence value'),
            };

            if ($newPosition < 0 || $newPosition > $length) {
                throw new StreamException('Unable to seek stream: position out of range');
            }

            $this->position = $newPosition;
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        if ($this->isResource && is_resource($this->resource)) {
            $result = fwrite($this->resource, $string);
            if (false === $result) {
                throw new StreamException('Unable to write to stream');
            }

            return $result;
        }

        $this->content .= $string;

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        if ($this->isResource && is_resource($this->resource)) {
            if ($length <= 0) {
                return '';
            }
            $result = fread($this->resource, $length);
            if (false === $result) {
                throw new StreamException('Unable to read from stream');
            }

            return $result;
        }

        if (null === $this->content) {
            return '';
        }

        $remaining = strlen($this->content) - $this->position;
        $readLength = min($length, $remaining);
        $result = substr($this->content, $this->position, $readLength);
        $this->position += $readLength;

        return $result;
    }

    public function getContents(): string
    {
        if ($this->isResource && is_resource($this->resource)) {
            $contents = stream_get_contents($this->resource);
            if (false === $contents) {
                throw new StreamException('Unable to read stream contents');
            }

            return $contents;
        }

        if (null === $this->content) {
            return '';
        }

        $remaining = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $remaining;
    }

    public function getMetadata(string|null $key = null)
    {
        if ($this->isResource && is_resource($this->resource)) {
            $metadata = stream_get_meta_data($this->resource);
            if (null === $key) {
                return $metadata;
            }

            return $metadata[$key] ?? null;
        }

        return null === $key ? [] : null;
    }
}
