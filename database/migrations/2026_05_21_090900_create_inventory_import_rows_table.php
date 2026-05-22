<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_import_batch_id')->constrained('inventory_import_batches')->cascadeOnDelete();
            $table->foreignId('producto_unidad_id')->nullable()->constrained('producto_unidades')->nullOnDelete();
            $table->string('source_sheet');
            $table->unsignedInteger('source_row_number');
            $table->string('row_hash');
            $table->string('status')->default('pending');
            $table->json('row_payload')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('conflict_details')->nullable();
            $table->json('resolution')->nullable();
            $table->timestamps();

            $table->unique(['inventory_import_batch_id', 'source_sheet', 'source_row_number'], 'inventory_import_rows_batch_sheet_row_unique');
            $table->index(['inventory_import_batch_id', 'status'], 'inventory_import_rows_batch_status_idx');
            $table->index(['source_sheet', 'status'], 'inventory_import_rows_sheet_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_import_rows');
    }
};
