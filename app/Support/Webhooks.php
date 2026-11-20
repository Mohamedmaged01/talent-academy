<?php

namespace App\Support;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends platform events to the configured webhooks. Deliveries run after the
 * HTTP response has been sent, so a slow receiver never delays the user, and
 * no queue worker is needed on the server.
 */
class Webhooks
{
    public static function dispatch(string $event, array $data): void
    {
        $hooks = Webhook::where('is_active', true)->get()->filter->listensTo($event);

        foreach ($hooks as $hook) {
            $payload = self::payload($event, $data);
            app()->terminating(fn () => self::deliver($hook, $event, $payload));
        }
    }

    public static function payload(string $event, array $data): array
    {
        return [
            'id' => (string) Str::uuid(),
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    /** POST the payload, signed with HMAC-SHA256 of the raw body using the webhook secret. */
    public static function deliver(Webhook $hook, string $event, array $payload): WebhookDelivery
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $started = microtime(true);
        $status = null;
        $excerpt = null;

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'X-Talent-Event' => $event,
                    'X-Talent-Signature' => 'sha256='.hash_hmac('sha256', $body, (string) $hook->secret),
                    'User-Agent' => 'TalentAcademy-Webhooks/1.0',
                ])
                ->withBody($body, 'application/json')
                ->post($hook->url);

            $status = $response->status();
            $excerpt = Str::limit($response->body(), 480);
        } catch (Throwable $e) {
            $excerpt = Str::limit($e->getMessage(), 480);
        }

        $hook->forceFill(['last_status' => $status, 'last_delivered_at' => now()])->save();

        return $hook->deliveries()->create([
            'event' => $event,
            'payload' => $payload,
            'status_code' => $status,
            'success' => $status !== null && $status >= 200 && $status < 300,
            'response_excerpt' => $excerpt,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }
}
