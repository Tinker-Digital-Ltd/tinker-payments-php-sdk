<?php

declare(strict_types=1);

namespace Tinker\Webhook;

use Tinker\Exception\InvalidPayloadException;
use Tinker\Exception\WebhookException;
use Tinker\Model\Transaction;

final class WebhookHandler
{
    /**
     * @param string|array<string, mixed> $payload
     */
    public function handle(string|array $payload): WebhookEvent
    {
        if (is_string($payload)) {
            $data = json_decode($payload, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidPayloadException('Invalid JSON payload: '.json_last_error_msg());
            }
        } else {
            $data = $payload;
        }

        if (!is_array($data)) {
            throw new InvalidPayloadException('Webhook payload must be an array');
        }

        $original = is_string($payload) ? json_decode($payload) : null;

        return new WebhookEvent($data, $original instanceof \stdClass ? $original : null);
    }

    public function handleFromRequest(): WebhookEvent
    {
        $payload = file_get_contents('php://input');
        if (false === $payload) {
            throw new WebhookException('Unable to read request body');
        }

        return $this->handle($payload);
    }

    /**
     * @param string|array<string, mixed> $payload
     */
    public function handleAsTransaction(string|array $payload): Transaction|null
    {
        $event = $this->handle($payload);

        return $event->toTransaction();
    }

    /**
     * @param WebhookEvent|string|array<string, mixed> $payload
     */
    public function verifySignature(WebhookEvent|string|array $payload, string $webhookSecret): bool
    {
        if ('' === $webhookSecret) {
            return false;
        }

        $event = $payload instanceof WebhookEvent ? $payload : $this->handle($payload);
        $signature = $event->security->signature;
        if ('HMAC-SHA256' !== $event->security->algorithm || !str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $payloadWithoutSecurity = [
            'id' => $event->id,
            'type' => $event->type,
            'source' => $event->source,
            'timestamp' => $event->timestamp,
            'data' => $this->sortObjects($event->signatureData),
            'meta' => $this->sortObjects($event->signatureMeta),
        ];

        $encoded = json_encode($payloadWithoutSecurity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (false === $encoded) {
            return false;
        }

        $encoded = str_replace(['<', '>', '&'], ['\\u003c', '\\u003e', '\\u0026'], $encoded);

        $computed = 'sha256='.hash_hmac('sha256', $encoded, $webhookSecret);

        return hash_equals($signature, $computed);
    }

    /** Go encoding/json sorts map keys while preserving list order. */
    private function sortObjects(mixed $value): mixed
    {
        if ($value instanceof \stdClass || (is_array($value) && !array_is_list($value))) {
            $fields = (array) $value;
            ksort($fields, SORT_STRING);
            foreach ($fields as &$field) {
                $field = $this->sortObjects($field);
            }

            return (object) $fields;
        }
        if (is_array($value)) {
            return array_map($this->sortObjects(...), $value);
        }

        return $value;
    }
}
