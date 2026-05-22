<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Client;
use App\Models\Habilitation;
use App\Models\Lead;
use App\Models\Pendiente;
use App\Models\SamuEvent;
use App\Models\User;
use App\Services\Samu\SamuReviewExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SamuReviewExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_export_service_streams_csv_for_current_view(): void
    {
        $commercial = User::factory()->create();
        $client = Client::query()->create([
            'name' => 'Cliente Export',
        ]);
        $lead = Lead::query()->create([
            'comercial_asignado_id' => $commercial->id,
            'created_by' => $commercial->id,
            'canal_origen' => 'telefono',
            'nombre' => 'Lead',
            'apellido' => 'Export',
            'telefono' => '3415550001',
            'email' => 'lead-export@test.local',
            'localidad' => 'Rosario',
            'provincia' => 'Santa Fe',
            'tipo_cliente' => 'Residencial',
            'producto_interes' => 'Silla salvaescaleras',
            'documentacion_cliente' => 'Prueba export',
            'orientacion_dada' => false,
            'estado_pipeline' => Lead::STAGE_INGRESADO,
            'resultado_final' => Lead::RESULTADO_ABIERTO,
            'area_responsable' => 'Comercial Venta',
        ]);
        $claim = Claim::query()->create([
            'client_id' => $client->id,
            'title' => 'Claim Export',
            'description' => 'Claim export',
            'status' => 'nuevo',
        ]);
        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'type' => 'diagnostico',
            'description' => 'Pendiente export',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'media',
        ]);
        $habilitation = Habilitation::query()->create([
            'client_id' => $client->id,
            'equipment' => 'Equipo Export',
            'status' => 'pendiente',
        ]);

        SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'external_id' => 'samu_export_1',
            'event_type' => 'commercial_interaction',
            'summary' => 'Evento exportable',
            'classification' => SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP,
            'interest_level' => 'alto',
            'processed' => true,
            'lead_id' => $lead->id,
            'client_id' => $client->id,
            'claim_id' => $claim->id,
            'pendiente_id' => $pendiente->id,
            'habilitation_id' => $habilitation->id,
            'payload' => [],
        ]);

        $response = app(SamuReviewExportService::class)->streamCurrentViewCsv(
            SamuEvent::query(),
            'todos',
        );

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('ID;"External ID";Evento;Resumen;Revision;', $content);
        $this->assertStringContainsString('samu_export_1', $content);
        $this->assertStringContainsString('Evento exportable', $content);
        $this->assertStringContainsString('Lead Export', $content);
        $this->assertStringContainsString('Cliente Export', $content);
    }
}
