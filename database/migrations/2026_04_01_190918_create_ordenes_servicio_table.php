<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('ordenes_servicio', function (Blueprint $table) {
    $table->id();
$table->foreignId('cliente_id')->constrained('clients')->cascadeOnDelete();
    $table->foreignId('tecnico_id')->nullable()->constrained()->nullOnDelete();

    $table->string('tipo_servicio');
    $table->text('descripcion')->nullable();

    $table->enum('estado', [
        'pendiente',
        'asignado',
        'en_camino',
        'en_sitio',
        'finalizado',
        'cancelado'
    ])->default('pendiente');

    $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');

    $table->timestamp('fecha_programada')->nullable();
    $table->timestamp('fecha_inicio')->nullable();
    $table->timestamp('fecha_fin')->nullable();

    // ubicación de la orden (por si cambia vs cliente)
    $table->decimal('latitud', 10, 7)->nullable();
    $table->decimal('longitud', 10, 7)->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio');
    }
};
