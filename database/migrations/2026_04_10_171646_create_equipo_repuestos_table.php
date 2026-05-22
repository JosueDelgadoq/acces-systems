<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_repuestos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->cascadeOnDelete();

            $table->foreignId('repuesto_id')
                ->constrained('repuestos')
                ->cascadeOnDelete();

            $table->integer('cantidad')->default(1);

            $table->timestamps();

            $table->unique(
                ['equipo_id', 'repuesto_id'],
                'er_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_repuestos');
    }
};