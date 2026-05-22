<?php

namespace Tests\Feature;

use App\Enums\Inventory\InventoryImportRowStatus;
use App\Models\InventoryImportBatch;
use App\Models\InventoryCategory;
use App\Models\InventoryLocation;
use App\Models\Producto;
use App\Models\ProductoUnidad;
use App\Models\ProductoVariante;
use App\Services\InventoryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InventoryImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_flags_duplicate_serials_inside_the_same_file(): void
    {
        Storage::fake('local');

        $path = $this->createWorkbook([
            'Orugas' => [
                ['Producto', 'Serie', 'Estado item', 'Condicion'],
                ['Oruga AXS', 'SER-001', 'Disponible', 'Nuevo'],
                ['Oruga AXS', 'SER-001', 'Disponible', 'Nuevo'],
            ],
        ]);

        $batch = InventoryImportBatch::query()->create([
            'file_name' => basename($path),
            'file_path' => $path,
            'file_disk' => 'local',
        ]);

        $previewed = app(InventoryImportService::class)->preview($batch);

        $this->assertSame(2, $previewed->rows()->count());
        $this->assertSame(2, $previewed->rows()->where('status', InventoryImportRowStatus::CONFLICT->value)->count());
        $this->assertSame(2, data_get($previewed->summary, 'conflict_rows'));
    }

    public function test_import_creates_inventory_unit_and_rollback_removes_created_units(): void
    {
        Storage::fake('local');

        $path = $this->createWorkbook([
            'Orugas' => [
                ['Producto', 'Serie', 'Estado item', 'Condicion', 'Ubicacion', 'Observaciones'],
                ['Oruga AXS', 'SER-100', 'Disponible', 'Nuevo', 'Rack A1', 'Ingreso inicial'],
            ],
        ]);

        $batch = InventoryImportBatch::query()->create([
            'file_name' => basename($path),
            'file_path' => $path,
            'file_disk' => 'local',
        ]);

        $service = app(InventoryImportService::class);

        $service->preview($batch);
        $imported = $service->import($batch->fresh());

        $this->assertDatabaseHas('inventory_categories', [
            'slug' => 'oruga',
        ]);
        $this->assertDatabaseHas('productos', [
            'nombre' => 'Oruga AXS',
            'tipo' => 'oruga',
        ]);
        $this->assertDatabaseHas('inventory_locations', [
            'code' => 'RACK-A1',
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'serial_number' => 'SER-100',
            'serial_number_normalized' => 'SER100',
            'estado' => ProductoUnidad::STATUS_AVAILABLE,
            'source_import_batch_id' => $imported->id,
        ]);

        $productId = Producto::query()->where('nombre', 'Oruga AXS')->value('id');
        $variantId = ProductoVariante::query()->where('producto_id', $productId)->value('id');
        $locationId = InventoryLocation::query()->where('code', 'RACK-A1')->value('id');
        $unitId = ProductoUnidad::query()->value('id');

        $rolledBack = $service->rollback($imported->fresh());

        $this->assertDatabaseMissing('productos', [
            'id' => $productId,
        ]);
        $this->assertDatabaseMissing('producto_variantes', [
            'id' => $variantId,
        ]);
        $this->assertDatabaseMissing('inventory_locations', [
            'id' => $locationId,
        ]);
        $this->assertDatabaseMissing('producto_unidades', [
            'id' => $unitId,
        ]);
        $this->assertSame(1, $rolledBack->rows()->where('status', InventoryImportRowStatus::ROLLED_BACK->value)->count());
    }

    public function test_import_supports_expanded_variant_side_and_usage_values(): void
    {
        Storage::fake('local');

        $path = $this->createWorkbook([
            'Plataformas y alerones' => [
                ['Producto', 'Lado', 'Uso', 'Serie', 'Estado item', 'Condicion'],
                ['Aleron telescopico', 'Bilateral', 'Mixto', 'SER-ALR-01', 'Disponible', 'Nuevo'],
                ['Plataforma compacta', 'Central', 'Interior', 'SER-PLT-01', 'Disponible', 'Nuevo'],
            ],
        ]);

        $batch = InventoryImportBatch::query()->create([
            'file_name' => basename($path),
            'file_path' => $path,
            'file_disk' => 'local',
        ]);

        $service = app(InventoryImportService::class);

        $previewed = $service->preview($batch);

        $this->assertSame(0, $previewed->rows()->where('status', InventoryImportRowStatus::ERROR->value)->count());
        $this->assertSame(0, $previewed->rows()->where('status', InventoryImportRowStatus::CONFLICT->value)->count());

        $service->import($batch->fresh());

        $this->assertDatabaseHas('producto_variantes', [
            'lado' => 'bilateral',
            'uso' => 'mixto',
        ]);
        $this->assertDatabaseHas('producto_variantes', [
            'lado' => 'central',
            'uso' => 'interior',
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'serial_number' => 'SER-ALR-01',
            'serial_number_normalized' => 'SERALR01',
        ]);
        $this->assertDatabaseHas('producto_unidades', [
            'serial_number' => 'SER-PLT-01',
            'serial_number_normalized' => 'SERPLT01',
        ]);
    }

    public function test_rollback_keeps_preexisting_product_and_variant_when_only_the_unit_was_created(): void
    {
        Storage::fake('local');

        $category = InventoryCategory::query()->create([
            'name' => 'Orugas',
            'slug' => 'oruga-test',
            'code' => 'ORUGA_TEST',
            'is_active' => true,
        ]);

        $product = Producto::query()->create([
            'nombre' => 'Oruga Preexistente',
            'tipo' => 'oruga',
            'category_id' => $category->id,
            'tracking_mode' => 'unitized',
        ]);

        $location = InventoryLocation::query()->create([
            'name' => 'Ubicacion Preexistente',
            'code' => 'UBICACION-PREEXISTENTE',
            'type' => 'warehouse',
        ]);

        $variant = ProductoVariante::query()->create([
            'producto_id' => $product->id,
            'modelo' => 'ORU-PRE',
            'subtype' => 'oruga',
        ]);

        $path = $this->createWorkbook([
            'Orugas' => [
                ['Producto', 'Modelo', 'Codigo interno', 'Estado item', 'Condicion', 'Ubicacion'],
                ['Oruga Preexistente', 'ORU-PRE', 'QA-KEEP-001', 'Disponible', 'Nuevo', 'Ubicacion Preexistente'],
            ],
        ]);

        $batch = InventoryImportBatch::query()->create([
            'file_name' => basename($path),
            'file_path' => $path,
            'file_disk' => 'local',
        ]);

        $service = app(InventoryImportService::class);

        $service->preview($batch);
        $imported = $service->import($batch->fresh());

        $unitId = ProductoUnidad::query()->where('source_import_batch_id', $imported->id)->value('id');

        $service->rollback($imported->fresh());

        $this->assertDatabaseHas('productos', [
            'id' => $product->id,
        ]);
        $this->assertDatabaseHas('inventory_categories', [
            'id' => $category->id,
        ]);
        $this->assertDatabaseHas('producto_variantes', [
            'id' => $variant->id,
        ]);
        $this->assertDatabaseHas('inventory_locations', [
            'id' => $location->id,
        ]);
        $this->assertDatabaseMissing('producto_unidades', [
            'id' => $unitId,
        ]);
    }

    public function test_rollback_removes_batch_created_category_and_location_when_they_become_orphaned(): void
    {
        Storage::fake('local');

        $path = $this->createWorkbook([
            'Orugas' => [
                ['Producto', 'Modelo', 'Codigo interno', 'Estado item', 'Condicion', 'Ubicacion'],
                ['Oruga Categoria Temporal', 'ORU-CAT', 'QA-CAT-001', 'Disponible', 'Nuevo', 'Ubicacion Temporal QA'],
            ],
        ]);

        $batch = InventoryImportBatch::query()->create([
            'file_name' => basename($path),
            'file_path' => $path,
            'file_disk' => 'local',
        ]);

        $service = app(InventoryImportService::class);

        $service->preview($batch);

        $row = $batch->fresh()->rows()->firstOrFail();
        $payload = $row->normalized_payload;
        $payload['category_slug'] = 'categoria_temporal_qa';
        $payload['category_name'] = 'Categoria Temporal QA';
        $row->forceFill([
            'normalized_payload' => $payload,
        ])->save();

        $imported = $service->import($batch->fresh());

        $categoryId = InventoryCategory::query()->where('slug', 'categoria-temporal-qa')->value('id');
        $locationId = InventoryLocation::query()->where('code', 'UBICACION-TEMPORAL-QA')->value('id');

        $this->assertNotNull($categoryId);
        $this->assertNotNull($locationId);

        $service->rollback($imported->fresh());

        $this->assertDatabaseMissing('inventory_categories', [
            'id' => $categoryId,
        ]);
        $this->assertDatabaseMissing('inventory_locations', [
            'id' => $locationId,
        ]);
    }

    protected function createWorkbook(array $sheets): string
    {
        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($sheets as $title => $rows) {
            $sheet = $sheetIndex === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet($sheetIndex);

            $sheet->setTitle($title);

            foreach ($rows as $rowNumber => $columns) {
                foreach (array_values($columns) as $columnNumber => $value) {
                    $sheet->setCellValueByColumnAndRow($columnNumber + 1, $rowNumber + 1, $value);
                }
            }

            $sheetIndex++;
        }

        $path = 'inventory-imports/tests/' . uniqid('inventory_', true) . '.xlsx';
        Storage::disk('local')->makeDirectory('inventory-imports/tests');

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('local')->path($path));

        return $path;
    }
}
