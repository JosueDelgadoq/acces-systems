<?php

namespace Tests\Feature;

use App\Models\CommercialGoal;
use App\Models\Lead;
use App\Models\Presupuesto;
use App\Models\Seguimiento;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\CommercialManagementAlertNotification;
use App\Notifications\CommercialPerformanceAlertNotification;
use App\Services\Alerts\CommercialAlertService;
use App\Support\Modules\LeadPermissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CommercialLeadFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_creating_a_lead_generates_one_initial_follow_up_and_one_history_entry_per_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $lead = Lead::query()->create([
            'nombre' => 'Maria',
            'apellido' => 'Lopez',
            'telefono' => '1133445566',
            'email' => 'maria@example.com',
            'canal_origen' => 'whatsapp',
        ]);

        $lead->load(['seguimientos', 'histories']);

        $this->assertSame($user->id, $lead->created_by);
        $this->assertSame($user->id, $lead->comercial_asignado_id);

        $this->assertDatabaseCount('seguimientos', 1);
        $this->assertDatabaseHas('seguimientos', [
            'lead_id' => $lead->id,
            'proxima_accion' => 'Primer contacto',
        ]);

        $this->assertDatabaseCount('lead_histories', 2);
        $this->assertEqualsCanonicalizing(
            ['created', 'follow_up_created'],
            $lead->histories->pluck('event_key')->all(),
        );
    }

    public function test_lead_accepts_manual_crm_id_and_persists_uploaded_document_paths(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $lead = Lead::query()->create([
            'crm_id' => 'CRM-MANUAL-001',
            'nombre' => 'Nora',
            'apellido' => 'Suarez',
            'telefono' => '1144455566',
            'email' => 'nora@example.com',
            'canal_origen' => 'mail',
            'producto_interes' => 'Silla salvaescalera',
            'documentacion_cliente_archivos' => [
                'leads/documentacion/plano.pdf',
                'leads/documentacion/foto-frente.jpg',
            ],
        ]);

        $lead->refresh();

        $this->assertSame('CRM-MANUAL-001', $lead->crm_id);
        $this->assertSame([
            'leads/documentacion/plano.pdf',
            'leads/documentacion/foto-frente.jpg',
        ], $lead->documentacion_cliente_archivos);
    }

    public function test_lead_history_view_renders_the_registered_timeline(): void
    {
        $user = User::factory()->create([
            'name' => 'Comercial Demo',
        ]);

        $this->actingAs($user);

        $lead = Lead::query()->create([
            'nombre' => 'Julia',
            'apellido' => 'Perez',
            'telefono' => '1199988877',
            'email' => 'julia@example.com',
            'canal_origen' => 'mail',
        ]);

        $html = view('filament.leads.history', [
            'record' => $lead->load('comercialAsignado', 'histories.user'),
        ])->render();

        $this->assertStringContainsString('Historia comercial del lead', $html);
        $this->assertStringContainsString('Lead ingresado', $html);
        $this->assertStringContainsString('Seguimiento programado', $html);
        $this->assertStringContainsString('Comercial Demo', $html);
    }

    public function test_reopening_a_closed_lead_resets_closing_fields_and_records_pipeline_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $lead = Lead::query()->create([
            'nombre' => 'Carlos',
            'apellido' => 'Gomez',
            'telefono' => '1122334455',
            'email' => 'carlos@example.com',
            'canal_origen' => 'telefono',
            'estado_pipeline' => Lead::STAGE_PERDIDO,
            'motivo_perdida' => 'Precio',
        ]);

        $this->assertSame(Lead::RESULTADO_PERDIDO, $lead->resultado_final);
        $this->assertNotNull($lead->fecha_cierre);
        $this->assertSame('Precio', $lead->motivo_perdida);

        $lead->update([
            'estado_pipeline' => Lead::STAGE_CONTACTADO,
        ]);

        $lead->refresh();

        $this->assertSame(Lead::STAGE_CONTACTADO, $lead->estado_pipeline);
        $this->assertSame(Lead::RESULTADO_ABIERTO, $lead->resultado_final);
        $this->assertNull($lead->fecha_cierre);
        $this->assertNull($lead->motivo_perdida);

        $pipelineEvent = $lead->histories()
            ->where('event_key', 'pipeline_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($pipelineEvent);
        $this->assertSame('Pipeline actualizado', $pipelineEvent->title);
        $this->assertStringContainsString('Perdido -> Contactado', $pipelineEvent->description ?? '');
    }

    public function test_authenticated_user_with_lead_permissions_can_open_leads_index_without_server_error(): void
    {
        Permission::findOrCreate(LeadPermissions::VIEW);

        $user = User::factory()->create();
        $user->givePermissionTo(LeadPermissions::VIEW);

        $this->actingAs($user);

        Lead::query()->create([
            'nombre' => 'Laura',
            'apellido' => 'Martinez',
            'telefono' => '1166677788',
            'email' => 'laura@example.com',
            'canal_origen' => 'whatsapp',
        ]);

        $response = $this
            ->get('/admin/leads');

        $response
            ->assertOk()
            ->assertSee('Leads')
            ->assertSee('Laura Martinez');
    }

    public function test_manager_can_open_commercial_metrics_and_see_sales_performance_breakdown(): void
    {
        Carbon::setTestNow('2026-05-14 10:00:00');

        Permission::findOrCreate(LeadPermissions::VIEW);
        Permission::findOrCreate(LeadPermissions::VIEW_ALL);

        $manager = User::factory()->create([
            'name' => 'Gerencia Comercial',
        ]);
        $manager->givePermissionTo(LeadPermissions::VIEW, LeadPermissions::VIEW_ALL);

        $ana = User::factory()->create(['name' => 'Ana']);
        $luisa = User::factory()->create(['name' => 'Luisa']);

        $this->actingAs($manager);

        $leadWon = Lead::query()->create([
            'nombre' => 'Marina',
            'apellido' => 'Diaz',
            'telefono' => '1111111111',
            'email' => 'marina@example.com',
            'canal_origen' => 'whatsapp',
            'fecha_ingreso' => now()->subDays(10)->toDateString(),
            'comercial_asignado_id' => $ana->id,
            'estado_pipeline' => Lead::STAGE_VENTA_CERRADA,
        ]);

        Seguimiento::query()->create([
            'lead_id' => $leadWon->id,
            'comercial_id' => $ana->id,
            'fecha_contacto' => now()->subDays(9)->toDateString(),
            'medio_contacto' => 'whatsapp',
            'resultado' => 'Cliente interesado',
            'proxima_accion' => 'Enviar propuesta',
            'fecha_proxima_accion' => now()->subDays(8)->toDateString(),
            'estado' => Seguimiento::STATUS_COMPLETADO,
        ]);

        Venta::query()->create([
            'lead_id' => $leadWon->id,
            'producto_instalado' => 'Silla salvaescalera',
            'monto_total' => 1500000,
            'estado' => 'Cerrada',
            'fecha_cierre' => now()->subDays(3)->toDateString(),
            'created_by' => $ana->id,
        ]);

        $leadLost = Lead::query()->create([
            'nombre' => 'Paula',
            'apellido' => 'Lopez',
            'telefono' => '1222222222',
            'email' => 'paula@example.com',
            'canal_origen' => 'mail',
            'fecha_ingreso' => now()->subDays(7)->toDateString(),
            'comercial_asignado_id' => $luisa->id,
            'estado_pipeline' => Lead::STAGE_PERDIDO,
            'motivo_perdida' => 'Precio',
        ]);

        Seguimiento::query()->create([
            'lead_id' => $leadLost->id,
            'comercial_id' => $luisa->id,
            'fecha_contacto' => now()->subDays(6)->toDateString(),
            'medio_contacto' => 'mail',
            'resultado' => 'Pidio revision de valores',
            'proxima_accion' => 'Recontactar',
            'fecha_proxima_accion' => now()->subDays(5)->toDateString(),
            'estado' => Seguimiento::STATUS_COMPLETADO,
        ]);

        Presupuesto::query()->create([
            'lead_id' => $leadLost->id,
            'producto_1' => 'Plataforma vertical',
            'precio_1' => 1800000,
            'presupuesto_definitivo' => 1800000,
            'fecha_envio' => now()->subDays(6)->toDateString(),
            'created_by' => $luisa->id,
        ]);

        CommercialGoal::query()->create([
            'user_id' => $ana->id,
            'goal_month' => now()->startOfMonth()->toDateString(),
            'target_leads' => 2,
            'target_contacts' => 1,
            'target_quotes' => 1,
            'target_sales' => 1,
            'target_revenue' => 1200000,
        ]);

        CommercialGoal::query()->create([
            'user_id' => $luisa->id,
            'goal_month' => now()->startOfMonth()->toDateString(),
            'target_leads' => 2,
            'target_contacts' => 2,
            'target_quotes' => 1,
            'target_sales' => 1,
            'target_revenue' => 1600000,
        ]);

        Lead::query()->create([
            'nombre' => 'Sofia',
            'apellido' => 'Mendez',
            'telefono' => '1333333333',
            'email' => 'sofia@example.com',
            'canal_origen' => 'redes_sociales',
            'fecha_ingreso' => now()->subDays(2)->toDateString(),
            'comercial_asignado_id' => $ana->id,
            'estado_pipeline' => Lead::STAGE_INGRESADO,
        ]);

        $response = $this->get('/admin/metricas-comerciales');

        $response
            ->assertOk()
            ->assertSee('Metricas comerciales')
            ->assertSee('Desempeno por vendedora')
            ->assertSee('Ana')
            ->assertSee('Luisa')
            ->assertSee('Objetivos mensuales')
            ->assertSee('Cumplida')
            ->assertSee('Precio alto')
            ->assertSee('WhatsApp');

        Carbon::setTestNow();
    }

    public function test_authorized_user_can_download_commercial_metrics_exports(): void
    {
        Carbon::setTestNow('2026-05-14 10:00:00');

        Permission::findOrCreate(LeadPermissions::VIEW);

        $user = User::factory()->create();
        $user->givePermissionTo(LeadPermissions::VIEW);

        $this->actingAs($user);

        Lead::query()->create([
            'nombre' => 'Rocio',
            'apellido' => 'Benitez',
            'telefono' => '1444444444',
            'email' => 'rocio@example.com',
            'canal_origen' => 'whatsapp',
            'fecha_ingreso' => now()->subDays(4)->toDateString(),
            'comercial_asignado_id' => $user->id,
            'estado_pipeline' => Lead::STAGE_CONTACTADO,
        ]);

        $xlsxResponse = $this->get('/admin/reportes/comercial/xlsx?from=2026-05-01&until=2026-05-31');

        $xlsxResponse
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertStringContainsString('.xlsx', (string) $xlsxResponse->headers->get('content-disposition'));

        $pdfResponse = $this->get('/admin/reportes/comercial/pdf?from=2026-05-01&until=2026-05-31');

        $pdfResponse
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString('.pdf', (string) $pdfResponse->headers->get('content-disposition'));

        Carbon::setTestNow();
    }

    public function test_commercial_alert_service_sends_email_to_commercial_and_management_without_duplicates(): void
    {
        Carbon::setTestNow('2026-05-20 10:00:00');

        config()->set('alerts.commercial.enabled', true);
        config()->set('alerts.commercial.include_commercial_email', true);
        config()->set('alerts.commercial.include_management_email', true);
        config()->set('alerts.commercial.stale_days', 3);
        config()->set('alerts.commercial.goal_progress_tolerance', 15);

        Permission::findOrCreate(LeadPermissions::ASSIGN);

        $manager = User::factory()->create([
            'name' => 'Gerencia',
            'email' => 'gerencia@example.com',
        ]);
        $manager->givePermissionTo(LeadPermissions::ASSIGN);

        $commercial = User::factory()->create([
            'name' => 'Lucia',
            'email' => 'lucia@example.com',
        ]);

        CommercialGoal::query()->create([
            'user_id' => $commercial->id,
            'goal_month' => now()->startOfMonth()->toDateString(),
            'target_leads' => 10,
            'target_contacts' => 8,
            'target_quotes' => 4,
            'target_sales' => 2,
            'target_revenue' => 0,
        ]);

        $this->actingAs($commercial);

        $lead = Lead::query()->create([
            'nombre' => 'Nora',
            'apellido' => 'Suarez',
            'telefono' => '1555555555',
            'email' => 'nora@example.com',
            'canal_origen' => 'whatsapp',
            'fecha_ingreso' => now()->subDays(8)->toDateString(),
            'comercial_asignado_id' => $commercial->id,
            'estado_pipeline' => Lead::STAGE_CONTACTADO,
        ]);

        Lead::query()
            ->whereKey($lead->id)
            ->update([
                'fecha_ultimo_seguimiento' => null,
            ]);

        Seguimiento::query()->create([
            'lead_id' => $lead->id,
            'comercial_id' => $commercial->id,
            'fecha_contacto' => now()->subDays(8)->toDateString(),
            'medio_contacto' => 'whatsapp',
            'resultado' => 'Pendiente de respuesta',
            'proxima_accion' => 'Recontactar',
            'fecha_proxima_accion' => now()->subDay()->toDateString(),
            'estado' => Seguimiento::STATUS_PENDIENTE,
        ]);

        Notification::fake();

        $summary = app(CommercialAlertService::class)->process();

        $this->assertSame(1, $summary['affected']);
        $this->assertSame(1, $summary['commercial_mail']);
        $this->assertSame(1, $summary['management_mail']);

        Notification::assertSentTo($commercial, CommercialPerformanceAlertNotification::class);
        Notification::assertSentTo($manager, CommercialManagementAlertNotification::class);
        $this->assertDatabaseCount('alert_dispatches', 2);

        $secondRun = app(CommercialAlertService::class)->process();

        $this->assertSame(1, $secondRun['affected']);
        $this->assertSame(0, $secondRun['commercial_mail']);
        $this->assertSame(0, $secondRun['management_mail']);
        $this->assertDatabaseCount('alert_dispatches', 2);

        Carbon::setTestNow();
    }
}
