<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\ServiceVisitEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceVisitTimelineViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_visit_timeline_view_renders_operational_events(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente View Timeline',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico View',
        ]);

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'diagnostico',
            'description' => 'Revision visual.',
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
            'event_type' => ServiceVisitEvent::TYPE_TRAVELING,
            'description' => 'Tecnico en traslado hacia la visita.',
            'lat' => -32.9442,
            'lng' => -60.6505,
        ]);

        $html = view('filament.service-visits.timeline', [
            'record' => $visit,
        ])->render();

        $this->assertStringContainsString('Timeline de visita', $html);
        $this->assertStringContainsString('En traslado', $html);
        $this->assertStringContainsString('Tecnico View', $html);
        $this->assertStringContainsString('-32.9442000', $html);
    }
}
