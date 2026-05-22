<?php

namespace App\Services\Alerts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppAlertSender
{
    public function send(string $phone, string $message, array $meta = []): void
    {
        $driver = config('alerts.whatsapp.driver', 'log');

        if ($driver === 'log') {
            Log::info('WhatsApp alert generated', [
                'phone' => $phone,
                'message' => $message,
                'meta' => $meta,
            ]);

            return;
        }

        if ($driver !== 'webhook') {
            throw new RuntimeException("Unsupported WhatsApp driver [{$driver}].");
        }

        $url = config('alerts.whatsapp.webhook_url');

        if (blank($url)) {
            throw new RuntimeException('WHATSAPP_WEBHOOK_URL is not configured.');
        }

        $request = Http::timeout((int) config('alerts.whatsapp.timeout', 10))
            ->acceptJson()
            ->asJson();

        $token = config('alerts.whatsapp.webhook_token');

        if (filled($token)) {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'phone' => $phone,
            'message' => $message,
            'meta' => $meta,
        ]);

        $response->throw();
    }
}
