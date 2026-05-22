<?php

namespace App\Services\Alerts;

use App\Models\AlertDispatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AlertDispatchStore
{
    protected ?bool $isAvailable = null;

    public function wasSent(string $dedupeKey): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return AlertDispatch::query()
            ->where('dedupe_key', $dedupeKey)
            ->exists();
    }

    public function record(
        string $alertKey,
        string $channel,
        ?string $target,
        Model $entity,
        string $dedupeKey,
        array $meta = [],
    ): void {
        if (! $this->isAvailable()) {
            return;
        }

        AlertDispatch::query()->create([
            'alert_key' => $alertKey,
            'channel' => $channel,
            'target' => $target,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'dedupe_key' => $dedupeKey,
            'meta' => $meta,
            'sent_at' => now(),
        ]);
    }

    public function makeDedupeKey(
        string $alertKey,
        string $channel,
        ?string $target,
        Model $entity,
        string $bucket,
    ): string {
        return sha1(implode('|', [
            $alertKey,
            $channel,
            $target ?: '-',
            $entity::class,
            (string) $entity->getKey(),
            $bucket,
        ]));
    }

    protected function isAvailable(): bool
    {
        return $this->isAvailable ??= Schema::hasTable('alert_dispatches');
    }
}
