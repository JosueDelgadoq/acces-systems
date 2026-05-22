<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_unidad_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_unidad_id')
                ->constrained('producto_unidades')
                ->cascadeOnDelete();
            $table->foreignId('producto_variante_id')
                ->nullable()
                ->constrained('producto_variantes')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('movement_type');
            $table->string('from_state')->nullable();
            $table->string('to_state')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['producto_unidad_id', 'created_at'], 'producto_unidad_movements_unit_created_idx');
            $table->index(['producto_variante_id', 'created_at'], 'producto_unidad_movements_variant_created_idx');
            $table->index(['movement_type', 'created_at'], 'producto_unidad_movements_type_created_idx');
            $table->index(['reference_type', 'reference_id'], 'producto_unidad_movements_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_unidad_movements');
    }
};
