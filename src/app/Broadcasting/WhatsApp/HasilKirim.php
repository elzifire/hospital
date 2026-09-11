<?php

namespace App\Broadcasting\WhatsApp;

/**
 * Hasil satu usaha kirim pesan WhatsApp dari sebuah driver.
 */
final readonly class HasilKirim
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $provider,
        public readonly ?string $messageId,
        public readonly ?string $error,
    ) {}

    public static function sukses(string $provider, ?string $messageId): self
    {
        return new self(true, $provider, $messageId, null);
    }

    public static function gagal(string $provider, string $error): self
    {
        return new self(false, $provider, null, $error);
    }
}
