<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceVisitModalViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_visit_modal_view_renders_visit_photos_and_report(): void
    {
        $client = Client::query()->create([
            'name' => 'Cliente Modal',
        ]);

        $technician = User::factory()->create();

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => 'diagnostico',
            'description' => 'Control visual.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);

        $visit = ServiceVisit::query()->create([
            'pendiente_id' => $pendiente->id,
            'technician_id' => $technician->id,
            'status' => ServiceVisit::STATUS_COMPLETED,
            'visit_type' => ServiceVisit::TYPE_DIAGNOSIS,
            'arrival_photo' => 'visits/llegada.jpg',
            'departure_photo' => 'visits/salida.jpg',
            'report' => 'Informe de prueba.',
            'arrival_time' => now()->subHour(),
            'departure_time' => now(),
        ]);

        $html = view('visits.modal', [
            'visit' => $visit,
        ])->render();

        $this->assertStringContainsString('Foto de llegada', $html);
        $this->assertStringContainsString('Foto de salida', $html);
        $this->assertStringContainsString('Informe de prueba.', $html);
    }

    public function test_service_visit_modal_view_handles_missing_visit(): void
    {
        $html = view('visits.modal', [
            'visit' => null,
        ])->render();

        $this->assertStringContainsString('No hay una visita registrada para este pendiente todavia.', $html);
    }
}
