<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_unidades', function (Blueprint $table): void {
            $table->string('inventory_code')->nullable()->unique();
            $table->string('serial_number')->nullable();
            $table->string('serial_number_normalized')->nullable()->unique();
            $table->string('condition')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('client_equipo_id')->nullable()->constrained('client_equipos')->nullOnDelete();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('acquired_at')->nullable();
            $table->foreignId('source_import_batch_id')->nullable()->constrained('inventory_import_batches')->nullOnDelete();
            $table->string('source_sheet')->nullable();
            $table->unsignedInteger('source_row_number')->nullable();
            $table->string('source_row_hash')->nullable();
        });

        $variantStates = DB::table('producto_variantes')->pluck('estado', 'id');

        foreach (DB::table('producto_unidades')->select('id', 'codigo_barra', 'producto_variante_id')->get() as $unit) {
            DB::table('producto_unidades')
                ->where('id', $unit->id)
                ->update([
                    'inventory_code' => $unit->codigo_barra,
                    'condition' => $variantStates[$unit->producto_variante_id] ?? 'nuevo',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('producto_unidades', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('location_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropConstrainedForeignId('client_equipo_id');
            $table->dropConstrainedForeignId('source_import_batch_id');
            $table->dropUnique('producto_unidades_inventory_code_unique');
            $table->dropUnique('producto_unidades_serial_number_normalized_unique');
            $table->dropColumn([
                'inventory_code',
                'serial_number',
                'serial_number_normalized',
                'condition',
                'notes',
                'installed_at',
                'acquired_at',
                'source_sheet',
                'source_row_number',
                'source_row_hash',
            ]);
        });
    }
};
