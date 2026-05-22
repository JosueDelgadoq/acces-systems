<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\SamuEvent;
use App\Models\User;
use App\Services\Samu\SamuIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SamuIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_reads_custom_erp_fields_and_populates_commercial_fields(): void
    {
        $role = Role::findOrCreate('comercial');

        $user = User::factory()->create([
            'name' => 'Comercial Samu',
        ]);
        $user->assignRole($role);

        $event = SamuEvent::query()->create([
            'source' => SamuEvent::SOURCE_SAMU,
            'processed' => false,
            'payload' => [
                'id' => 'samu_accented_1',
                'type' => 'meeting',
                'duration' => 1,
                'name' => 'Llamada comercial ERP',
                'summary' => 'Cliente solicita informacion sobre una silla subescalera y acepta continuar por WhatsApp.',
                'extractor' => [
                    'nombre_cliente_erp' => 'Juan Perez',
                    'telefono_cliente_erp' => '54111536693597',
                    'interes_erp' => 'alto',
                    'siguiente_paso_erp' => 'Enviar mensaje por WhatsApp despues de las 18:00',
                    'etapa_pipeline_erp' => 'presupuesto',
                    'circuito_erp' => 'venta_presupuesto',
                ],
            ],
        ]);

        $processed = app(SamuIntegrationService::class)->process($event->fresh());

        $this->assertSame('alto', $processed->interest_level);
        $this->assertSame(Lead::STAGE_PRESUPUESTO, $processed->pipeline_stage);
        $this->assertSame(SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP, $processed->classification);
        $this->assertSame('Enviar mensaje por WhatsApp despues de las 18:00', $processed->next_step);
        $this->assertNotNull($processed->lead_id);

        $lead = Lead::query()->findOrFail($processed->lead_id);

        $this->assertSame('Juan', $lead->nombre);
        $this->assertSame('Perez', $lead->apellido);
        $this->assertSame('54111536693597', $lead->telefono);
        $this->assertSame(Lead::STAGE_PRESUPUESTO, $lead->estado_pipeline);
    }
}
