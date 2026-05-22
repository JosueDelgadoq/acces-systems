<?php

namespace App\Services\Samu;

use App\Jobs\ProcessSamuWebhookJob;
use App\Models\SamuEvent;
use App\Models\SamuEventNote;
use App\Models\User;

class SamuReviewService
{
    public function recatalogar(
        SamuEvent $event,
        string $classification,
        ?string $notes = null,
        ?User $reviewer = null,
    ): SamuEvent {
        $event->forceFill([
            'manual_classification' => $classification,
            'manual_review_notes' => filled($notes) ? trim((string) $notes) : null,
            'manual_reviewed_by' => $reviewer?->id,
            'manual_reviewed_at' => now(),
            'processed' => false,
            'processed_at' => null,
            'error_message' => null,
        ])->save();

        ProcessSamuWebhookJob::dispatch($event)->afterCommit();

        return $event->fresh();
    }

    public function discard(
        SamuEvent $event,
        string $notes,
        ?User $reviewer = null,
    ): SamuEvent {
        return $this->recatalogar(
            $event,
            SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            $notes,
            $reviewer,
        );
    }

    public function clearManualOverride(SamuEvent $event, ?User $reviewer = null): SamuEvent
    {
        $event->forceFill([
            'manual_classification' => null,
            'manual_review_notes' => null,
            'manual_reviewed_by' => $reviewer?->id,
            'manual_reviewed_at' => now(),
            'processed' => false,
            'processed_at' => null,
            'error_message' => null,
        ])->save();

        ProcessSamuWebhookJob::dispatch($event)->afterCommit();

        return $event->fresh();
    }

    public function reprocess(SamuEvent $event): SamuEvent
    {
        $event->forceFill([
            'processed' => false,
            'processed_at' => null,
            'error_message' => null,
        ])->save();

        ProcessSamuWebhookJob::dispatch($event)->afterCommit();

        return $event->fresh();
    }

    public function markProcessed(SamuEvent $event): SamuEvent
    {
        $event->forceFill([
            'processed' => true,
            'processed_at' => now(),
            'error_message' => null,
        ])->save();

        return $event->fresh();
    }

    public function addInternalNote(
        SamuEvent $event,
        string $note,
        ?User $author = null,
        bool $isSystem = false,
    ): SamuEventNote {
        $trimmedNote = trim($note);

        $event->forceFill([
            'manual_reviewed_by' => $author?->id,
            'manual_reviewed_at' => now(),
        ])->save();

        return $event->notes()->create([
            'user_id' => $author?->id,
            'note' => $trimmedNote,
            'is_system' => $isSystem,
        ]);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<int, string>
     */
    public function unlinkRelations(SamuEvent $event, array $fields): array
    {
        $selectedFields = collect($fields)
            ->filter(fn ($field): bool => is_string($field) && array_key_exists($field, SamuEvent::UNLINKABLE_RELATION_FIELDS))
            ->unique()
            ->values();

        if ($selectedFields->isEmpty()) {
            return [];
        }

        $updates = $selectedFields
            ->mapWithKeys(fn (string $field): array => [$field => null])
            ->all();

        $event->forceFill($updates)->save();

        return $selectedFields->all();
    }
}
