<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\User;
use App\Services\ServiceVisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceVisitWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_accept_returns_fresh_visit_with_loaded_pendiente_client_and_technician(): void
    {
        [$technician, $visit] = $this->makeAssignedVisit('diagnostico');

        $result = app(ServiceVisitWorkflowService::class)->accept($visit, $technician);

        $this->assertInstanceOf(ServiceVisit::class, $result);
        $this->assertSame(ServiceVisit::STATUS_ACCEPTED, $result->status);
        $this->assertTrue($result->relationLoaded('pendiente'));
        $this->assertNotNull($result->pendiente);
        $this->assertTrue($result->pendiente->relationLoaded('client'));
        $this->assertTrue($result->relationLoaded('technician'));
        $this->assertSame($technician->id, $result->technician?->id);
    }

    public function test_start_returns_fresh_visit_with_loaded_pendiente_client_and_technician(): void
    {
        [$technician, $visit] = $this->makeAssignedVisit('instalacion');

        $result = app(ServiceVisitWorkflowService::class)->start($visit, $technician, [
            'lat' => -32.9500,
            'lng' => -60.6600,
        ]);

        $this->assertInstanceOf(ServiceVisit::class, $result);
        $this->assertSame(ServiceVisit::STATUS_IN_PROGRESS, $result->status);
        $this->assertTrue($result->relationLoaded('pendiente'));
        $this->assertNotNull($result->pendiente);
        $this->assertTrue($result->pendiente->relationLoaded('client'));
        $this->assertTrue($result->relationLoaded('technician'));
        $this->assertSame($technician->id, $result->technician?->id);
    }

    public function test_finish_returns_fresh_visit_with_loaded_pendiente_client_and_technician(): void
    {
        [$technician, $visit] = $this->makeAssignedVisit('reparacion');

        $workflow = app(ServiceVisitWorkflowService::class);
        $workflow->start($visit, $technician, [
            'lat' => -32.9500,
            'lng' => -60.6600,
        ]);

        $result = $workflow->finish($visit->fresh(), $technician, [
            'report' => 'Cierre tecnico correcto.',
            'lat' => -32.9550,
            'lng' => -60.6650,
        ]);

        $this->assertInstanceOf(ServiceVisit::class, $result);
        $this->assertSame(ServiceVisit::STATUS_COMPLETED, $result->status);
        $this->assertTrue($result->relationLoaded('pendiente'));
        $this->assertNotNull($result->pendiente);
        $this->assertTrue($result->pendiente->relationLoaded('client'));
        $this->assertTrue($result->relationLoaded('technician'));
        $this->assertSame($technician->id, $result->technician?->id);
    }

    public function test_mobile_payload_for_technician_keeps_expected_contract(): void
    {
        [$technician] = $this->makeAssignedVisit('diagnostico');

        $payload = app(ServiceVisitWorkflowService::class)->mobilePayloadForTechnician($technician);

        $this->assertCount(1, $payload);
        $this->assertSame([
            'id',
            'status',
            'status_label',
            'visit_type',
            'visit_type_label',
            'arrival_time',
            'departure_time',
            'arrival_photo_url',
            'departure_photo_url',
            'report',
            'pendiente_id',
            'pendiente_type',
            'pendiente_type_label',
            'pendiente_status',
            'pendiente_status_label',
            'workflow_stage',
            'workflow_stage_label',
            'service_order_number',
            'client_name',
            'client_address',
            'client_availability',
            'description',
            'priority',
            'priority_label',
            'due_date',
            'assigned_at',
            'estimated_time',
            'review_notes',
            'work_order',
            'required_tools',
            'operational_zone',
            'operational_zone_label',
            'neighborhood',
            'can_accept',
            'can_start',
            'can_finish',
        ], array_keys($payload[0]));
    }

    /**
     * @return array{0: User, 1: ServiceVisit}
     */
    protected function makeAssignedVisit(string $type): array
    {
        $client = Client::query()->create([
            'name' => 'Cliente Workflow',
            'address' => 'Calle 123',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico Workflow',
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => $type,
            'description' => 'Orden operativa de prueba.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
            'service_order_number' => 'OT-WORKFLOW-001',
            'review_notes' => 'Checklist de prueba.',
            'required_tools' => ['kit_basico'],
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        return [$technician, $visit];
    }
}
