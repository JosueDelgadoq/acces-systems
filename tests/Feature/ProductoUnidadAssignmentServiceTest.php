<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Pendiente;
use App\Models\Producto;
use App\Models\ProductoUnidad;
use App\Models\ProductoUnidadAssignment;
use App\Models\ProductoVariante;
use App\Models\ServiceVisit;
use App\Models\User;
use App\Services\ProductoUnidadAssignmentService;
use App\Services\ServiceVisitWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoUnidadAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_units_can_be_reserved_and_released_from_pending_planification(): void
    {
        [$technician, $pendiente, $units] = $this->makePendingWithUnits('instalacion');

        $service = app(ProductoUnidadAssignmentService::class);

        $service->syncForPendiente($pendiente, [$units[0]->id, $units[1]->id], $technician, 'Reserva tecnica.');

        $this->assertDatabaseCount('producto_unidad_assignments', 2);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[0]->id,
            'estado' => ProductoUnidad::STATUS_RESERVED,
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[1]->id,
            'estado' => ProductoUnidad::STATUS_RESERVED,
        ]);
        $this->assertSame(2, $pendiente->fresh()->activeUnitAssignments()->count());

        $service->syncForPendiente($pendiente->fresh(), [$units[0]->id], $technician, 'Ajuste de planificacion.');

        $this->assertSame(1, $pendiente->fresh()->activeUnitAssignments()->count());
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[0]->id,
            'estado' => ProductoUnidad::STATUS_RESERVED,
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[1]->id,
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
        ]);
        $this->assertDatabaseHas('producto_unidad_assignments', [
            'producto_unidad_id' => $units[1]->id,
            'status' => ProductoUnidadAssignment::STATUS_RELEASED,
        ]);
    }

    public function test_installation_visit_marks_reserved_unit_as_installed_on_finish(): void
    {
        [$technician, $pendiente, $units] = $this->makePendingWithUnits('instalacion');

        $assignmentService = app(ProductoUnidadAssignmentService::class);
        $workflow = app(ServiceVisitWorkflowService::class);

        $assignmentService->syncForPendiente($pendiente, [$units[0]->id], $technician, 'Reserva para instalacion.');

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        $workflow->accept($visit, $technician);

        $this->assertDatabaseHas('producto_unidad_assignments', [
            'producto_unidad_id' => $units[0]->id,
            'service_visit_id' => $visit->id,
            'status' => ProductoUnidadAssignment::STATUS_ASSIGNED,
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[0]->id,
            'estado' => ProductoUnidad::STATUS_ASSIGNED,
        ]);

        $workflow->start($visit->fresh(), $technician, [
            'lat' => -32.9500,
            'lng' => -60.6600,
        ]);

        $workflow->finish($visit->fresh(), $technician, [
            'report' => 'Instalacion completada.',
            'lat' => -32.9550,
            'lng' => -60.6650,
        ]);

        $this->assertDatabaseHas('producto_unidad_assignments', [
            'producto_unidad_id' => $units[0]->id,
            'service_visit_id' => $visit->id,
            'status' => ProductoUnidadAssignment::STATUS_INSTALLED,
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[0]->id,
            'estado' => ProductoUnidad::STATUS_INSTALLED,
        ]);
        $this->assertSame(0, $pendiente->fresh()->activeUnitAssignments()->count());
    }

    public function test_non_installation_visit_releases_reserved_unit_back_to_available(): void
    {
        [$technician, $pendiente, $units] = $this->makePendingWithUnits('diagnostico');

        $assignmentService = app(ProductoUnidadAssignmentService::class);
        $workflow = app(ServiceVisitWorkflowService::class);

        $assignmentService->syncForPendiente($pendiente, [$units[0]->id], $technician, 'Reserva para diagnostico.');

        $visit = ServiceVisit::query()
            ->where('pendiente_id', $pendiente->id)
            ->firstOrFail();

        $workflow->accept($visit, $technician);
        $workflow->start($visit->fresh(), $technician, [
            'lat' => -32.9500,
            'lng' => -60.6600,
        ]);
        $workflow->finish($visit->fresh(), $technician, [
            'report' => 'Diagnostico completado.',
            'lat' => -32.9550,
            'lng' => -60.6650,
        ]);

        $this->assertDatabaseHas('producto_unidad_assignments', [
            'producto_unidad_id' => $units[0]->id,
            'service_visit_id' => $visit->id,
            'status' => ProductoUnidadAssignment::STATUS_RELEASED,
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[0]->id,
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
        ]);
    }

    public function test_cancelling_pending_releases_reserved_units_even_without_mobile_finish(): void
    {
        [$technician, $pendiente, $units] = $this->makePendingWithUnits('reparacion');

        app(ProductoUnidadAssignmentService::class)->syncForPendiente(
            $pendiente,
            [$units[0]->id],
            $technician,
            'Reserva previa a la reparacion.',
        );

        $pendiente->update([
            'status' => Pendiente::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('producto_unidad_assignments', [
            'producto_unidad_id' => $units[0]->id,
            'status' => ProductoUnidadAssignment::STATUS_RELEASED,
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'id' => $units[0]->id,
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
        ]);
    }

    /**
     * @return array{0: User, 1: Pendiente, 2: array<int, ProductoUnidad>}
     */
    protected function makePendingWithUnits(string $type): array
    {
        $client = Client::query()->create([
            'name' => 'Cliente Inventario',
            'address' => 'Calle 123',
            'city' => 'Rosario',
            'state' => 'Santa Fe',
            'postal_code' => '2000',
        ]);

        $technician = User::factory()->create([
            'name' => 'Tecnico Inventario',
            'last_lat' => -32.9442,
            'last_lng' => -60.6505,
        ]);

        $product = Producto::query()->create([
            'nombre' => 'Silla salvaescaleras',
            'tipo' => 'equipo',
        ]);

        $variant = ProductoVariante::query()->create([
            'producto_id' => $product->id,
            'estado' => 'nuevo',
            'uso' => 'interior',
            'lado' => 'derecho',
            'modelo' => 'Linea 500',
        ]);

        $units = [
            ProductoUnidad::query()->create([
                'producto_variante_id' => $variant->id,
                'codigo_barra' => 'PU-TEST-001',
                'estado' => ProductoUnidad::STATUS_AVAILABLE,
            ]),
            ProductoUnidad::query()->create([
                'producto_variante_id' => $variant->id,
                'codigo_barra' => 'PU-TEST-002',
                'estado' => ProductoUnidad::STATUS_AVAILABLE,
            ]),
        ];

        $pendiente = Pendiente::query()->create([
            'client_id' => $client->id,
            'user_id' => $technician->id,
            'type' => $type,
            'description' => 'Orden operativa con equipos reservados.',
            'status' => Pendiente::STATUS_PENDING,
            'priority' => 'alta',
            'service_order_number' => 'OT-INV-001',
            'review_notes' => 'Checklist de reserva.',
            'required_tools' => ['kit_basico'],
        ]);

        return [$technician, $pendiente, $units];
    }
}
