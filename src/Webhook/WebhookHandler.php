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

        return new WebhookEvent($data);
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
}
