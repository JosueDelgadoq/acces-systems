<?php

namespace Tests\Feature;

use App\Jobs\ProcessSamuWebhookJob;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Habilitation;
use App\Models\Lead;
use App\Models\Pendiente;
use App\Models\SamuEvent;
use App\Models\User;
use App\Services\Samu\SamuReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SamuReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recatalogar_and_clear_manual_override_queue_reprocess_with_audit(): void
    {
        Queue::fake();

        $reviewer = User::factory()->create();
        $event = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Evento a revisar',
            'processed' => true,
            'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'payload' => [],
        ]);

        $service = app(SamuReviewService::class);

        $service->recatalogar(
            $event,
            SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
            'Corresponde a habilitacion.',
            $reviewer,
        );

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'manual_classification' => SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
            'manual_reviewed_by' => $reviewer->id,
            'processed' => false,
        ]);

        Queue::assertPushed(ProcessSamuWebhookJob::class, 1);

        $service->clearManualOverride($event->fresh(), $reviewer);

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'manual_classification' => null,
            'manual_review_notes' => null,
            'manual_reviewed_by' => $reviewer->id,
            'processed' => false,
        ]);

        Queue::assertPushed(ProcessSamuWebhookJob::class, 2);
    }

    public function test_discard_reprocess_and_mark_processed_apply_expected_state(): void
    {
        Queue::fake();

        $reviewer = User::factory()->create();
        $event = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Evento descartable',
            'processed' => false,
            'error_message' => 'Fallo previo',
            'payload' => [],
        ]);

        $service = app(SamuReviewService::class);

        $service->discard($event, 'No corresponde al CRM.', $reviewer);

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'manual_classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'manual_reviewed_by' => $reviewer->id,
            'processed' => false,
            'error_message' => null,
        ]);

        $service->reprocess($event->fresh());

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'processed' => false,
            'processed_at' => null,
            'error_message' => null,
        ]);

        $service->markProcessed($event->fresh());

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'processed' => true,
            'error_message' => null,
        ]);

        Queue::assertPushed(ProcessSamuWebhookJob::class, 2);
    }

    public function test_unlink_relations_only_nulls_allowed_relation_fields(): void
    {
        $commercial = User::factory()->create();
        $client = Client::query()->create([
            'name' => 'Cliente Samu',
        ]);
        $lead = Lead::query()->create([
            'comercial_asignado_id' => $commercial->id,
            'created_by' => $commercial->id,
            'canal_origen' => 'telefono',
            'nombre' => 'Lead',
            'apellido' => 'Samu',
            'telefono' => '3415550000',
            'email' => 'lead-samu@test.local',
            'localidad' => 'Rosario',
            'provincia' => 'Santa Fe',
            'tipo_cliente' => 'Residencial',
            'producto_interes' => 'Silla salvaescaleras',
            'documentacion_cliente' => 'Prueba',
            'orientacion_dada' => false,
            'estado_pipeline' => Lead::STAGE_INGRESADO,
            'resultado_final' => Lead::RESULTADO_ABIERTO,
            'area_responsable' => 'Comercial Venta',
        ]);
        $claim = Claim::query()->create([
            'client_id' => $client->id,
            'title' => 'Claim Samu',
            'description' => 'Claim de prueba Samu',
            'status' => 'nuevo',
        ]);
        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'type' => 'diagnostico',
            'description' => 'Pendiente Samu',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);
        $habilitation = Habilitation::query()->create([
            'client_id' => $client->id,
            'equipment' => 'Equipo Samu',
            'status' => 'pendiente',
        ]);

        $event = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Evento vinculado',
            'lead_id' => $lead->id,
            'client_id' => $client->id,
            'claim_id' => $claim->id,
            'pendiente_id' => $pendiente->id,
            'habilitation_id' => $habilitation->id,
            'payload' => [],
        ]);

        $fields = app(SamuReviewService::class)->unlinkRelations($event, [
            'lead_id',
            'pendiente_id',
            'campo_invalido',
            'lead_id',
        ]);

        $this->assertSame(['lead_id', 'pendiente_id'], $fields);

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'lead_id' => null,
            'client_id' => $client->id,
            'claim_id' => $claim->id,
            'pendiente_id' => null,
            'habilitation_id' => $habilitation->id,
        ]);
    }

    public function test_add_internal_note_creates_audited_note_and_updates_last_manual_review(): void
    {
        $reviewer = User::factory()->create();
        $event = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'summary' => 'Evento con nota interna',
            'processed' => true,
            'payload' => [],
        ]);

        $note = app(SamuReviewService::class)->addInternalNote(
            $event,
            'Revisar este caso con Tahis antes de moverlo.',
            $reviewer,
        );

        $this->assertDatabaseHas('samu_event_notes', [
            'id' => $note->id,
            'samu_event_id' => $event->id,
            'user_id' => $reviewer->id,
            'note' => 'Revisar este caso con Tahis antes de moverlo.',
            'is_system' => false,
        ]);

        $this->assertDatabaseHas('samu_events', [
            'id' => $event->id,
            'manual_reviewed_by' => $reviewer->id,
        ]);
    }
}
