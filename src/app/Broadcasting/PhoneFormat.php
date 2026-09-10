<?php

namespace App\Broadcasting;

/**
 * Konversi nomor HP lokal (08…) ke format internasional WhatsApp (628…).
 */
final class PhoneFormat
{
    public static function toWa(?string $nomor): ?string
    {
        $digit = preg_replace('/\D/', '', (string) $nomor);

        if ($digit === '') {
            return null;
        }

        if (str_starts_with($digit, '62')) {
            return $digit;
        }

        if (str_starts_with($digit, '0')) {
            return '62'.substr($digit, 1);
        }

        if (str_starts_with($digit, '8')) {
            return '62'.$digit;
        }

        // Format lain (mis. kode negara berbeda) dipakai apa adanya.
        return $digit;
    }
}
