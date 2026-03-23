<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('producto_instalado');
            $table->date('fecha_instalacion');
            $table->boolean('garantia_activa')->default(true);
            $table->boolean('servicio_mantenimiento')->default(false);
            $table->date('proximo_servicio')->nullable();
            $table->enum('nivel_satisfaccion', ['excelente', 'bueno', 'regular', 'mala'])->nullable();
            $table->text('resena_obtenida')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postventas');
    }
};


