<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * A provider call failed. $type, $title and $explanation say why in plain words
 * (see AiErrorExplainer); $detail keeps the provider's own message.
 */
class AiException extends RuntimeException
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $explanation,
        public readonly ?int $status = null,
        public readonly ?string $detail = null,
        public readonly ?string $provider = null,
    ) {
        parent::__construct($title);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'explanation' => $this->explanation,
            'status' => $this->status,
            'detail' => $this->detail,
            'provider' => $this->provider,
        ];
    }
}
