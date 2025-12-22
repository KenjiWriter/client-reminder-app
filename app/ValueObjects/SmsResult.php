<?php

namespace App\ValueObjects;

class SmsResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(?string $providerMessageId = null): self
    {
        return new self(
            success: true,
            providerMessageId: $providerMessageId,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
