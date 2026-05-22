<?php

namespace Tests\Feature;

use App\Models\SamuEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SamuReviewInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_scopes_split_samu_events_into_expected_buckets(): void
    {
        $pendingQueue = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Pendiente de cola',
            'processed' => false,
            'payload' => [],
        ]);

        $unclassified = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Sin catalogar',
            'processed' => true,
            'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'payload' => [],
        ]);

        $manualOverride = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Override manual',
            'processed' => true,
            'classification' => SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
            'manual_classification' => SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
            'payload' => [],
        ]);

        $discarded = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Descartado',
            'processed' => true,
            'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'manual_classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'payload' => [],
        ]);

        $error = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Con error',
            'processed' => false,
            'error_message' => 'No se pudo procesar',
            'payload' => [],
        ]);

        $lowSignal = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'La duracion de la llamada no fue suficiente para elaborar un analisis detallado.',
            'processed' => true,
            'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'classification_reason' => 'La interaccion fue demasiado corta o no aporto informacion suficiente para automatizar acciones.',
            'payload' => [],
        ]);

        $ok = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Procesado OK',
            'processed' => true,
            'classification' => SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP,
            'payload' => [],
        ]);

        $this->assertEqualsCanonicalizing(
            [$pendingQueue->id, $unclassified->id, $manualOverride->id, $discarded->id, $error->id],
            SamuEvent::query()->needsReview()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$unclassified->id, $error->id],
            SamuEvent::query()->unclassifiedReview()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$manualOverride->id],
            SamuEvent::query()->withManualOverride()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$discarded->id],
            SamuEvent::query()->discarded()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$error->id],
            SamuEvent::query()->withErrors()->pluck('id')->all(),
        );

        $this->assertEqualsCanonicalizing(
            [$lowSignal->id],
            SamuEvent::query()->lowSignal()->pluck('id')->all(),
        );

        $this->assertSame('Baja senal', $lowSignal->getReviewStatusMeta()['label']);
        $this->assertSame('OK', $ok->getReviewStatusMeta()['label']);
    }

    public function test_review_status_meta_prioritizes_error_and_discard_logic(): void
    {
        $event = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Evento manual con error',
            'processed' => false,
            'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'manual_classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'error_message' => 'Error de integracion',
            'payload' => [],
        ]);

        $this->assertSame([
            'label' => 'Con error',
            'color' => 'danger',
        ], $event->getReviewStatusMeta());
    }
}
