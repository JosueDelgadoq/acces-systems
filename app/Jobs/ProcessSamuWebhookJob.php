<?php

namespace App\Jobs;

use App\Models\SamuEvent;
use App\Services\Samu\SamuIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessSamuWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public SamuEvent $samuEvent,
    ) {
    }

    public function handle(SamuIntegrationService $service): void
    {
        $event = $this->samuEvent->fresh();

        if (! $event) {
            Log::warning('Samu webhook job skipped because event no longer exists.');

            return;
        }

        try {
            $service->process($event);

            $event->forceFill([
                'processed' => true,
                'processed_at' => now(),
                'error_message' => null,
            ])->save();

            Log::info('Samu webhook job completed.', [
                'samu_event_id' => $event->id,
                'external_id' => $event->external_id,
            ]);
        } catch (Throwable $exception) {
            $message = $exception->getMessage();

            $event->forceFill([
                'processed' => false,
                'processed_at' => null,
                'error_message' => Str::limit($message, 65535, ''),
            ])->save();

            Log::error('Samu webhook job failed.', [
                'samu_event_id' => $event->id,
                'external_id' => $event->external_id,
                'error' => $message,
            ]);

            throw $exception;
        }
    }
}
