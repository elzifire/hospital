<?php

namespace App\Http\Controllers;

use App\Broadcasting\WhatsApp\WebhookHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook masuk Meta WhatsApp Cloud API.
 *
 *   GET  /whatsapp/webhook → verifikasi endpoint (hub.challenge).
 *   POST /whatsapp/webhook → terima balasan/pesan pasien, simpan ke
 *   MessageReply (modul Respon). Tanda tangan X-Hub-Signature-256
 *   diverifikasi dengan WA_APP_SECRET.
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(protected WebhookHandler $webhook) {}

    public function verify(Request $request)
    {
        $token = (string) config('whatsapp.webhook.verify_token');

        if (
            $request->query('hub_mode') === 'subscribe'
            && $token !== ''
            && hash_equals($token, (string) $request->query('hub_verify_token'))
        ) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->webhook->validasi($request)) {
            abort(403);
        }

        $diproses = $this->webhook->proses($request);

        return response()->json(['status' => 'ok', 'diproses' => $diproses]);
    }
}
