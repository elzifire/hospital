<?php

namespace App\Http\Controllers\Webhook;

use App\Broadcasting\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint webhook WhatsApp — dipanggil WAHA dari luar (tanpa sesi login).
 *
 * Autentikasi (salah satu, sesuai konfigurasi):
 *  1. HMAC sha512  — WAHA env WHATSAPP_HOOK_HMAC_KEY → header X-Webhook-Hmac
 *  2. Token statis — WAHA env WHATSAPP_HOOK_CUSTOM_HEADERS=X-Webhook-Token:<nilai>
 *                   → header X-Webhook-Token (atau ?token=... untuk uji manual)
 *  3. Tanpa keduanya (dev) — permintaan diterima apa adanya
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(protected WebhookService $service) {}

    public function handle(Request $request): JsonResponse
    {
        if (! $this->otentik($request)) {
            return response()->json(['error' => 'Webhook tidak terautentikasi.'], 401);
        }

        $balasan = $this->service->proses($request->all());

        // Selalu 200 agar WAHA tidak mengirim ulang event.
        return response()->json(['ok' => true, 'disimpan' => (bool) $balasan]);
    }

    protected function otentik(Request $request): bool
    {
        $secret = (string) config('whatsapp.webhook.secret');

        if ($secret !== '') {
            $hmac = hash_hmac('sha512', $request->getContent(), $secret);

            return hash_equals($hmac, (string) $request->header('X-Webhook-Hmac'));
        }

        $token = (string) config('whatsapp.webhook.verify_token');

        if ($token !== '') {
            $dikirim = (string) ($request->header('X-Webhook-Token') ?? $request->query('token', ''));

            return hash_equals($token, $dikirim);
        }

        return true;
    }
}
