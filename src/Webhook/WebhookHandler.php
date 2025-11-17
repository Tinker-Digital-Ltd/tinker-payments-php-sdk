<?php

declare(strict_types=1);

namespace Tinker\Webhook;

use Tinker\Exception\InvalidPayloadException;
use Tinker\Exception\WebhookException;
use Tinker\Model\Transaction;

final class WebhookHandler
{
    public function handle(string|array $payload): Transaction
    {
        if (is_string($payload)) {
            $data = json_decode($payload, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidPayloadException('Invalid JSON payload: '.json_last_error_msg());
            }
        } else {
            $data = $payload;
        }

        return new Transaction($data);
    }

    public function handleFromRequest(): Transaction
    {
        $payload = file_get_contents('php://input');
        if (false === $payload) {
            throw new WebhookException('Unable to read request body');
        }

        return $this->handle($payload);
    }
}
