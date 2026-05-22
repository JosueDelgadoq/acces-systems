<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_variante_repuestos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_variante_id')
                ->constrained('producto_variantes')
                ->cascadeOnDelete();

            $table->foreignId('repuesto_id')
                ->constrained('repuestos')
                ->cascadeOnDelete();

            $table->integer('cantidad')->default(1);
            $table->boolean('obligatorio')->default(true);

            $table->timestamps();

            // 🔥 FIX DEL ERROR
            $table->unique(
                ['producto_variante_id', 'repuesto_id'],
                'pvr_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_variante_repuestos');
    }
};