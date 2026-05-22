<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Conservation;
use App\Models\User;
use App\Services\ConservationContractService;
use App\Services\PendientesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConservationContractServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_last_service_marks_contract_for_renewal(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Conservacion',
        ]);

        $technician = User::factory()->create();

        $conservation = Conservation::query()->create([
            'client_id' => $client->id,
            'start_date' => '2026-01-01',
            'expiration_date' => '2026-12-31',
            'frequency' => 'mensual',
            'current_service_number' => 12,
            'total_services' => 12,
            'next_service_date' => '2026-12-01',
            'contract_status' => Conservation::STATUS_ACTIVE,
        ]);

        $visit = app(ConservationContractService::class)->registerService($conservation, [
            'date' => '2026-12-01',
            'technician_id' => $technician->id,
            'remito' => '6845',
            'notes' => 'Ultima visita del ciclo.',
        ]);

        $conservation->refresh();

        $this->assertSame(Conservation::STATUS_RENEWAL_REQUIRED, $conservation->contract_status);
        $this->assertSame(12, $conservation->current_service_number);
        $this->assertSame('12/12', $conservation->service_progress);
        $this->assertNull($conservation->next_service_date);
        $this->assertNotNull($conservation->renewal_required_at);
        $this->assertSame(12, $visit->service_number);
        $this->assertSame(12, $visit->contract_total_services);
    }

    public function test_renewing_contract_resets_cycle_and_stores_history(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Renovado',
        ]);

        $user = User::factory()->create();

        $conservation = Conservation::query()->create([
            'client_id' => $client->id,
            'start_date' => '2026-01-01',
            'expiration_date' => '2026-12-31',
            'frequency' => 'mensual',
            'current_service_number' => 12,
            'total_services' => 12,
            'next_service_date' => null,
            'contract_status' => Conservation::STATUS_RENEWAL_REQUIRED,
            'contract_cycle_number' => 1,
            'last_service_date' => '2026-12-01',
            'renewal_required_at' => '2026-12-01',
        ]);

        $renewal = app(ConservationContractService::class)->renewContract($conservation, [
            'renewed_at' => '2026-12-05',
            'new_start_date' => '2027-01-01',
            'new_expiration_date' => '2027-12-31',
            'frequency' => 'mensual',
            'total_services' => 12,
            'notes' => 'Renovacion anual por 12 mantenimientos.',
        ], $user);

        $conservation->refresh();

        $this->assertSame(Conservation::STATUS_ACTIVE, $conservation->contract_status);
        $this->assertSame(2, $conservation->contract_cycle_number);
        $this->assertSame(1, $conservation->current_service_number);
        $this->assertSame('01/12', $conservation->service_progress);
        $this->assertSame('2027-02-01', $conservation->next_service_date?->toDateString());
        $this->assertNull($conservation->renewal_required_at);

        $this->assertDatabaseHas('conservation_renewals', [
            'id' => $renewal->id,
            'conservation_id' => $conservation->id,
            'renewed_by' => $user->id,
            'previous_cycle_number' => 1,
            'new_cycle_number' => 2,
            'previous_total_services' => 12,
            'previous_completed_services' => 12,
            'new_total_services' => 12,
        ]);
    }

    public function test_pending_services_ignore_contracts_waiting_for_renewal(): void
    {
        $clientA = Client::query()->create(['name' => 'Cliente Activo']);
        $clientB = Client::query()->create(['name' => 'Cliente Renovar']);

        Conservation::query()->create([
            'client_id' => $clientA->id,
            'start_date' => now()->subMonths(2)->toDateString(),
            'expiration_date' => now()->addMonths(2)->toDateString(),
            'frequency' => 'mensual',
            'current_service_number' => 2,
            'total_services' => 6,
            'next_service_date' => now()->subDay()->toDateString(),
            'contract_status' => Conservation::STATUS_ACTIVE,
        ]);

        Conservation::query()->create([
            'client_id' => $clientB->id,
            'start_date' => now()->subMonths(12)->toDateString(),
            'expiration_date' => now()->addMonths(1)->toDateString(),
            'frequency' => 'mensual',
            'current_service_number' => 6,
            'total_services' => 6,
            'next_service_date' => now()->subDay()->toDateString(),
            'contract_status' => Conservation::STATUS_RENEWAL_REQUIRED,
            'renewal_required_at' => now()->subDay()->toDateString(),
        ]);

        $pendientes = PendientesService::get()->values();

        $this->assertCount(1, $pendientes);
        $this->assertSame('Cliente Activo', $pendientes->first()['client']);
    }
}
