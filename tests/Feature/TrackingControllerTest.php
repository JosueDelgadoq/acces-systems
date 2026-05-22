<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\ServiceVisitEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TrackingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_api_lists_assigned_visits_with_operational_context(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Mobile',
            'address' => 'Calle 456',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $technician = User::factory()->create(['name' => 'Tecnico Mobile']);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'diagnostico',
            'description' => 'Equipo sin energia.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
            'service_order_number' => 'OT-2026-001',
            'review_notes' => 'Llevar multimetro.',
            'required_tools' => ['multimetro', 'kit_basico'],
        ]);

        Sanctum::actingAs($technician);

        $response = $this->getJson('/api/my-visits');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'pendiente_id' => $pendiente->id,
                'visit_type' => ServiceVisit::TYPE_DIAGNOSIS,
                'visit_type_label' => 'Diagnostico',
                'service_order_number' => 'OT-2026-001',
                'client_name' => 'Cliente Mobile',
                'description' => 'Equipo sin energia.',
            ])
            ->assertJsonFragment([
                'required_tools' => ['multimetro', 'kit_basico'],
            ]);
    }

    public function test_mobile_api_can_start_and_finish_assigned_visit(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Ejecucion',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico API',
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'reparacion',
            'description' => 'Cambio de modulo averiado.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        Sanctum::actingAs($technician);

        $this->postJson("/api/visits/{$visit->id}/start", [
            'lat' => -32.9500,
            'lng' => -60.6600,
        ])->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'visit_id' => $visit->id,
                'status' => ServiceVisit::STATUS_IN_PROGRESS,
            ]);

        $visit->refresh();
        $pendiente->refresh();

        $this->assertSame(ServiceVisit::STATUS_IN_PROGRESS, $visit->status);
        $this->assertNotNull($visit->arrival_time);
        $this->assertSame(Pendiente::STATUS_IN_PROGRESS, $pendiente->status);
        $this->assertNotNull($pendiente->performed_at);

        $this->postJson("/api/visits/{$visit->id}/finish", [
            'report' => 'Trabajo completado sin novedades.',
            'lat' => -32.9550,
            'lng' => -60.6650,
        ])->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'visit_id' => $visit->id,
                'status' => ServiceVisit::STATUS_COMPLETED,
            ]);

        $visit->refresh();
        $pendiente->refresh();
        $technician->refresh();

        $this->assertSame(ServiceVisit::STATUS_COMPLETED, $visit->status);
        $this->assertSame('Trabajo completado sin novedades.', $visit->report);
        $this->assertNotNull($visit->departure_time);
        $this->assertSame(Pendiente::STATUS_COMPLETED, $pendiente->status);
        $this->assertSame('Trabajo completado sin novedades.', $pendiente->control_summary);
        $this->assertNotNull($pendiente->completed_at);
        $this->assertSame('finished', $technician->technician_status);
    }

    public function test_mobile_api_records_visit_timeline_events_and_exposes_them(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Timeline',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico Timeline',
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'instalacion',
            'description' => 'Montaje de equipo.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        Sanctum::actingAs($technician);

        $this->postJson("/api/visits/{$visit->id}/accept")
            ->assertOk()
            ->assertJsonFragment([
                'visit_id' => $visit->id,
                'status' => ServiceVisit::STATUS_ACCEPTED,
            ]);

        $this->postJson('/api/tracking/status', [
            'status' => 'traveling',
        ])->assertOk();

        $this->postJson("/api/visits/{$visit->id}/start", [
            'lat' => -32.9500,
            'lng' => -60.6600,
        ])->assertOk();

        $this->postJson("/api/visits/{$visit->id}/finish", [
            'report' => 'Instalacion completada.',
            'lat' => -32.9550,
            'lng' => -60.6650,
        ])->assertOk();

        $this->assertDatabaseHas('service_visit_events', [
            'service_visit_id' => $visit->id,
            'pendiente_id' => $pendiente->id,
            'user_id' => $technician->id,
            'event_type' => ServiceVisitEvent::TYPE_ACCEPTED,
        ]);

        $response = $this->getJson("/api/visits/{$visit->id}/events");

        $response
            ->assertOk()
            ->assertJsonFragment([
                'visit_id' => $visit->id,
                'pendiente_id' => $pendiente->id,
            ])
            ->assertJsonCount(4, 'events')
            ->assertJsonPath('events.0.event_type', ServiceVisitEvent::TYPE_ACCEPTED)
            ->assertJsonPath('events.1.event_type', ServiceVisitEvent::TYPE_TRAVELING)
            ->assertJsonPath('events.2.event_type', ServiceVisitEvent::TYPE_STARTED)
            ->assertJsonPath('events.3.event_type', ServiceVisitEvent::TYPE_FINISHED)
            ->assertJsonPath('events.1.technician.name', 'Tecnico Timeline');
    }

    public function test_traveling_status_deduplicates_events_for_two_minutes(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');

        try {
            $client = Client::query()->create([
                'name' => 'Cliente Ruta',
            ]);

            $technician = User::factory()->create([
                'name' => 'Tecnico Ruta',
                'last_lat' => -32.9442,
                'last_lng' => -60.6505,
            ]);

            $pendiente = Pendiente::query()->create([
                'client_id' => $client->id,
                'user_id' => $technician->id,
                'type' => 'diagnostico',
                'description' => 'Salida tecnica.',
                'status' => Pendiente::STATUS_PENDING,
                'priority' => 'media',
            ]);

            $visit = ServiceVisit::query()
                ->where('pendiente_id', $pendiente->id)
                ->firstOrFail();

            Sanctum::actingAs($technician);

            $this->postJson("/api/visits/{$visit->id}/accept")->assertOk();

            $this->postJson('/api/tracking/status', [
                'status' => 'traveling',
            ])->assertOk();

            $this->postJson('/api/tracking/status', [
                'status' => 'traveling',
            ])->assertOk();

            $this->assertSame(
                1,
                ServiceVisitEvent::query()
                    ->where('service_visit_id', $visit->id)
                    ->where('event_type', ServiceVisitEvent::TYPE_TRAVELING)
                    ->count(),
            );

            Carbon::setTestNow(now()->addMinutes(3));

            $this->postJson('/api/tracking/status', [
                'status' => 'traveling',
            ])->assertOk();

            $this->assertSame(
                2,
                ServiceVisitEvent::query()
                    ->where('service_visit_id', $visit->id)
                    ->where('event_type', ServiceVisitEvent::TYPE_TRAVELING)
                    ->count(),
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_web_visit_events_endpoint_returns_timeline_for_assigned_technician(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Web Timeline',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico Web',
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'reparacion',
            'description' => 'Seguimiento web.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        ServiceVisitEvent::query()->create([
            'service_visit_id' => $visit->id,
            'pendiente_id' => $pendiente->id,
            'user_id' => $technician->id,
            'event_type' => ServiceVisitEvent::TYPE_ACCEPTED,
            'description' => 'Aceptacion desde test.',
        ]);

        $this->actingAs($technician)
            ->getJson("/tracking/visits/{$visit->id}/events")
            ->assertOk()
            ->assertJsonFragment([
                'event_type' => ServiceVisitEvent::TYPE_ACCEPTED,
                'description' => 'Aceptacion desde test.',
            ]);
    }

    public function test_authenticated_tracking_live_returns_enriched_operational_payload(): void
    {
        Permission::findOrCreate('tracking.view');

        $technician = User::factory()->create([
            'name' => 'Tecnico Live',
            'tracking_enabled' => true,
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
            'last_seen_at' => now(),
        ]);
        $technician->givePermissionTo('tracking.view');

        $client = Client::query()->create([
            'name' => 'Cliente Live',
            'address' => 'Av. Operativa 123',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'diagnostico',
            'description' => 'Relevamiento en sitio.',
            'service_address' => 'Planta Rosario',
            'operational_zone' => 'centro',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        $this->actingAs($technician)
            ->getJson('/tracking/live')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $technician->id,
                'name' => 'Tecnico Live',
                'current_visit_id' => $visit->id,
                'current_pendiente_id' => $pendiente->id,
                'current_client_name' => 'Cliente Live',
                'current_client_address' => 'Planta Rosario',
                'current_operational_zone' => 'centro',
                'current_operational_zone_label' => 'Zona Centro',
            ])
            ->assertJsonStructure([[
                'id',
                'name',
                'last_lat',
                'last_lng',
                'last_seen_at',
                'last_seen_at_iso',
                'last_seen_human',
                'technician_status',
                'status',
                'status_label',
                'status_color',
                'tracking_enabled',
                'is_online',
                'current_visit',
                'current_visit_id',
                'current_visit_status',
                'current_visit_status_label',
                'current_pendiente_id',
                'current_pendiente_type',
                'current_pendiente_type_label',
                'current_client_name',
                'current_client_address',
            ]]);
    }

    public function test_public_tracking_live_keeps_minimal_legacy_payload(): void
    {
        User::factory()->create([
            'name' => 'Tecnico Publico',
            'tracking_enabled' => true,
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
            'last_seen_at' => now(),
            'technician_status' => 'paused',
        ]);

        $response = $this->getJson('/api/tracking/live');

        $response
            ->assertOk()
            ->assertJsonStructure([[
                'id',
                'name',
                'last_lat',
                'last_lng',
                'last_seen_at',
                'technician_status',
            ]])
            ->assertJsonMissingPath('0.current_visit')
            ->assertJsonMissingPath('0.current_client_name');
    }

    public function test_mobile_visit_events_endpoint_keeps_expected_json_contract(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Contract',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico Contract',
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'diagnostico',
            'description' => 'Contrato timeline.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
        ]);

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        Sanctum::actingAs($technician);

        $this->postJson("/api/visits/{$visit->id}/accept")->assertOk();

        $response = $this->getJson("/api/visits/{$visit->id}/events");

        $response->assertOk();

        $payload = $response->json();
        $event = $payload['events'][0];

        $this->assertSame([
            'success',
            'visit_id',
            'pendiente_id',
            'events',
        ], array_keys($payload));

        $this->assertSame([
            'id',
            'service_visit_id',
            'pendiente_id',
            'user_id',
            'technician',
            'event_type',
            'event_type_label',
            'description',
            'lat',
            'lng',
            'metadata',
            'created_at',
            'updated_at',
        ], array_keys($event));

        $this->assertSame([
            'id',
            'name',
        ], array_keys($event['technician']));

        $this->assertSame(ServiceVisitEvent::TYPE_ACCEPTED, $event['event_type']);
        $this->assertSame('Aceptada', $event['event_type_label']);
        $this->assertSame($visit->id, $event['service_visit_id']);
        $this->assertSame($pendiente->id, $event['pendiente_id']);
        $this->assertSame($technician->id, $event['user_id']);
        $this->assertSame($technician->id, $event['technician']['id']);
        $this->assertSame('Tecnico Contract', $event['technician']['name']);
    }
}
