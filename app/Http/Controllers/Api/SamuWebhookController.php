<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSamuWebhookJob;
use App\Models\SamuEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SamuWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.samu.webhook_secret');

        if (blank($secret)) {
            Log::warning('Samu webhook rejected because secret is not configured.');

            return response()->json([
                'success' => false,
                'message' => 'Samu webhook not configured',
            ], 503);
        }

        $receivedToken = (string) $request->header('X-Samu-Token', '');

        if (! hash_equals($secret, $receivedToken)) {
            Log::warning('Samu webhook unauthorized.', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook token',
            ], 401);
        }

        $payload = $request->json()->all();

        if (! is_array($payload) || $payload === []) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload',
            ], 422);
        }

        $validator = Validator::make($payload, [
            'external_id' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email'],
            'summary' => ['nullable', 'string'],
            'transcript' => ['nullable', 'string'],
            'interest_level' => ['nullable', 'string', 'max:100'],
            'next_step' => ['nullable', 'string'],
            'pipeline_stage' => ['nullable', 'string', 'max:100'],
            'objections' => ['nullable', 'array'],
            'objections.*' => ['string'],
            'tasks_detected' => ['nullable', 'array'],
            'tasks_detected.*.title' => ['required_with:tasks_detected', 'string', 'max:255'],
            'tasks_detected.*.due_date' => ['nullable', 'date'],
            'tasks_detected.*.priority' => ['nullable', 'string', 'max:100'],
        ]);

        $hasUsefulIdentity = filled($payload['external_id'] ?? null)
            || filled($payload['client_name'] ?? null)
            || filled($payload['email'] ?? null)
            || filled($payload['phone'] ?? null)
            || filled($payload['summary'] ?? null);

        if ($validator->fails() || ! $hasUsefulIdentity) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        $extractor = is_array($payload['extractor'] ?? null) ? $payload['extractor'] : [];
        $actionItems = collect($payload['actionItems'] ?? [])
            ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
            ->map(fn (string $item): array => [
                'title' => trim($item),
                'due_date' => null,
                'priority' => 'media',
            ])
            ->values()
            ->all();

        $objections = collect([
            $payload['objections'] ?? null,
            $extractor['objeciones_detectadas'] ?? null,
            $extractor['razones_para_retraso_o_duda'] ?? null,
        ])
            ->filter()
            ->flatMap(function ($value): array {
                if (is_array($value)) {
                    return $value;
                }

                return [trim((string) $value)];
            })
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        $event = SamuEvent::query()->create([
            'external_id' => $payload['external_id'] ?? $payload['id'] ?? null,
            'source' => SamuEvent::SOURCE_SAMU,
            'event_type' => $payload['event_type'] ?? $payload['callTypeName'] ?? $payload['type'] ?? null,
            'payload' => $payload,
            'summary' => $payload['summary'] ?? null,
            'transcript' => $payload['transcript'] ?? null,
            'interest_level' => $payload['interest_level'] ?? null,
            'next_step' => $payload['next_step'] ?? $extractor['siguiente_paso_acordado'] ?? ($actionItems[0]['title'] ?? null),
            'pipeline_stage' => $payload['pipeline_stage'] ?? null,
            'objections' => $objections !== [] ? $objections : null,
            'tasks_detected' => $payload['tasks_detected'] ?? ($actionItems !== [] ? $actionItems : null),
        ]);

        ProcessSamuWebhookJob::dispatch($event)->afterCommit();

        Log::info('Samu webhook received.', [
            'samu_event_id' => $event->id,
            'external_id' => $event->external_id,
            'event_type' => $event->event_type,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook received',
        ]);
    }
}
