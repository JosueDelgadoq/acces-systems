<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoUnidad;
use App\Models\ProductoUnidadMovement;
use App\Models\ProductoVariante;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        Permission::findOrCreate('inventario.update');
    }

    public function test_scan_requires_inventory_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/scan', ['codigo_barra' => 'ABC-123'])
            ->assertForbidden();
    }

    public function test_scan_does_not_create_placeholder_records_for_unknown_barcode(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventario.update');

        $this->actingAs($user)
            ->postJson('/scan', ['codigo_barra' => 'ABC-123'])
            ->assertNotFound()
            ->assertJson([
                'error' => 'barcode_not_found',
            ]);

        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('producto_variantes', 0);
        $this->assertDatabaseCount('producto_unidades', 0);
    }

    public function test_scan_returns_registered_unit_information(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventario.update');

        $producto = Producto::query()->create([
            'nombre' => 'Silla salvaescalera',
            'tipo' => 'silla',
        ]);

        $variante = ProductoVariante::query()->create([
            'producto_id' => $producto->id,
            'estado' => 'nuevo',
            'uso' => 'interior',
            'lado' => 'derecho',
            'modelo' => 'X1',
        ]);

        ProductoUnidad::query()->create([
            'producto_variante_id' => $variante->id,
            'codigo_barra' => 'ABC-123',
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
        ]);

        ProductoUnidad::query()->create([
            'producto_variante_id' => $variante->id,
            'codigo_barra' => 'ABC-456',
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
        ]);

        $this->actingAs($user)
            ->postJson('/scan', ['codigo_barra' => 'ABC-123'])
            ->assertOk()
            ->assertJson([
                'scan_type' => 'unit',
                'codigo_barra' => 'ABC-123',
                'producto' => 'Silla salvaescalera',
                'tipo' => 'silla',
                'estado' => ProductoUnidad::STATUS_AVAILABLE,
                'estado_label' => 'Disponible',
                'uso' => 'interior',
                'lado' => 'derecho',
                'modelo' => 'X1',
                'stock' => 2,
                'stock_disponible' => 2,
                'total_unidades' => 2,
            ]);
    }

    public function test_movimiento_updates_unit_state_and_records_history(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventario.update');

        $producto = Producto::query()->create([
            'nombre' => 'Motor',
            'tipo' => 'motor',
        ]);

        $variante = ProductoVariante::query()->create([
            'producto_id' => $producto->id,
            'modelo' => 'M1',
        ]);

        $unidad = ProductoUnidad::query()->create([
            'producto_variante_id' => $variante->id,
            'codigo_barra' => 'PU-0001',
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
        ]);

        $this->actingAs($user)
            ->postJson('/movimiento', [
                'producto_unidad_id' => $unidad->id,
                'estado_destino' => ProductoUnidad::STATUS_INSTALLED,
                'motivo' => 'instalacion',
                'observaciones' => 'Salida a campo para visita tecnica',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'unit' => [
                    'codigo_barra' => 'PU-0001',
                    'estado' => ProductoUnidad::STATUS_INSTALLED,
                    'estado_label' => 'Instalado',
                ],
            ]);

        $this->assertDatabaseHas('producto_unidades', [
            'id' => $unidad->id,
            'estado' => ProductoUnidad::STATUS_INSTALLED,
        ]);

        $this->assertDatabaseHas('producto_unidad_movements', [
            'producto_unidad_id' => $unidad->id,
            'movement_type' => 'instalacion',
            'from_state' => ProductoUnidad::STATUS_AVAILABLE,
            'to_state' => ProductoUnidad::STATUS_INSTALLED,
            'reason' => 'instalacion',
        ]);

        $this->assertSame(2, ProductoUnidadMovement::query()->where('producto_unidad_id', $unidad->id)->count());
    }

    public function test_variant_entry_generates_serialized_units_and_movements(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventario.update');

        $producto = Producto::query()->create([
            'nombre' => 'Plataforma',
            'tipo' => 'plataforma',
        ]);

        $variante = ProductoVariante::query()->create([
            'producto_id' => $producto->id,
            'modelo' => 'PX',
        ]);

        $this->actingAs($user)
            ->postJson('/movimiento', [
                'producto_variante_id' => $variante->id,
                'tipo' => 'entrada',
                'cantidad' => 3,
                'motivo' => 'compra',
                'observaciones' => 'Ingreso de deposito inicial',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'created_units' => 3,
                'stock' => 3,
                'total_unidades' => 3,
            ]);

        $this->assertDatabaseCount('producto_unidades', 3);
        $this->assertDatabaseCount('producto_unidad_movements', 3);
    }
}
