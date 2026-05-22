<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendienteTechnicalBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_can_store_technical_board_defaults(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Tecnico',
            'address' => 'Calle 123',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'type' => 'diagnostico',
            'description' => 'Equipo sin respuesta.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
        ]);

        $this->assertSame('Calle 123, Rosario, Santa Fe, 2000', $pendiente->fresh()->service_address);
        $this->assertSame('alta', $pendiente->fresh()->suggested_priority);
        $this->assertSame(Pendiente::STAGE_POSTVENTA, $pendiente->fresh()->workflow_stage);
        $this->assertSame(
            array_keys(Pendiente::getToolOptionsForType('diagnostico')),
            $pendiente->fresh()->required_tools,
        );
    }

    public function test_pending_moves_through_assignment_signature_and_completion(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Flujo',
        ]);

        $reviewer = User::factory()->create(['name' => 'Alfredo']);
        $technician = User::factory()->create(['name' => 'Tecnico Uno']);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'type' => 'reparacion',
            'description' => 'Cambio de modulo.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        $pendiente->update([
            'reviewed_by_user_id' => $reviewer->id,
            'assigned_by_user_id' => $reviewer->id,
            'user_id' => $technician->id,
            'review_notes' => 'Validado por Alfredo.',
        ]);

        $pendiente->refresh();

        $this->assertSame(Pendiente::STAGE_ASSIGNED, $pendiente->workflow_stage);
        $this->assertNotNull($pendiente->assigned_at);

        $pendiente->update([
            'technician_acknowledged' => true,
            'technician_signature_name' => 'Tecnico Uno',
        ]);

        $pendiente->refresh();

        $this->assertSame(Pendiente::STAGE_ACCEPTED, $pendiente->workflow_stage);
        $this->assertNotNull($pendiente->technician_signature_at);

        $pendiente->update([
            'status' => Pendiente::STATUS_COMPLETED,
        ]);

        $pendiente->refresh();

        $this->assertSame(Pendiente::STAGE_COMPLETED, $pendiente->workflow_stage);
        $this->assertNotNull($pendiente->completed_at);
        $this->assertNotNull($pendiente->performed_at);
    }

    public function test_assigned_pending_creates_a_service_visit_with_operational_type(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Instalacion',
            'address' => 'Av. Tecnica 123',
            'city' => 'Cordoba',
            'state' => 'Cordoba',
            'postal_code' => '5000',
        ]);

        $technician = User::factory()->create(['name' => 'Tecnico Campo']);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'instalacion',
            'description' => 'Instalacion de equipo nuevo.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->first();

        $this->assertNotNull($visit);
        $this->assertSame($technician->id, $visit->technician_id);
        $this->assertSame(ServiceVisit::STATUS_PENDING, $visit->status);
        $this->assertSame(ServiceVisit::TYPE_INSTALLATION, $visit->visit_type);
    }

    public function test_reassigning_a_started_pending_rolls_over_to_a_new_visit(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Rollover',
        ]);

        $firstTechnician = User::factory()->create(['name' => 'Tecnico Uno']);
        $secondTechnician = User::factory()->create(['name' => 'Tecnico Dos']);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $firstTechnician->id,
            'type' => 'diagnostico',
            'description' => 'Diagnostico en planta.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        $firstVisit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        $firstVisit->update([
            'status' => ServiceVisit::STATUS_IN_PROGRESS,
            'arrival_time' => now(),
            'report' => 'Relevamiento inicial.',
        ]);

        $pendiente->update([
            'user_id' => $secondTechnician->id,
        ]);

        $visits = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $visits);
        $this->assertSame(ServiceVisit::STATUS_COMPLETED, $visits->first()->status);
        $this->assertStringContainsString('Visita reasignada desde pendiente.', (string) $visits->first()->report);
        $this->assertSame($secondTechnician->id, $visits->last()->technician_id);
        $this->assertSame(ServiceVisit::STATUS_PENDING, $visits->last()->status);
        $this->assertSame(ServiceVisit::TYPE_DIAGNOSIS, $visits->last()->visit_type);
    }

    public function test_history_is_not_recorded_for_whitespace_only_notes_changes(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Historial',
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'type' => 'diagnostico',
            'description' => 'Control de historial.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
            'notes' => 'Observacion inicial',
        ]);

        $this->assertCount(1, $pendiente->fresh()->histories);

        $pendiente->update([
            'notes' => '  Observacion inicial  ',
        ]);

        $this->assertCount(1, $pendiente->fresh()->histories);

        $pendiente->update([
            'notes' => 'Observacion actualizada',
        ]);

        $this->assertCount(2, $pendiente->fresh()->histories);
    }

    public function test_filament_listing_scope_can_eager_load_latest_visit_without_ambiguous_columns(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Listado',
        ]);

        $technician = User::factory()->create();

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'type' => 'diagnostico',
            'description' => 'Revision de visita.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        ServiceVisit::query()->create([
            'pendiente_id' => $pendiente->id,
            'technician_id' => $technician->id,
            'status' => 'pending',
        ]);

        $records = Pendiente::query()
            ->forFilamentListing()
            ->get();

        $this->assertCount(1, $records);
        $this->assertTrue($records->first()->relationLoaded('latestVisit'));
        $this->assertSame($pendiente->id, $records->first()->latestVisit?->pendiente_id);
    }
}
