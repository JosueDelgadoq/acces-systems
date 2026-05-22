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
Schema::create('fotos_servicio', function (Blueprint $table) {
    $table->id();
    $table->foreignId('orden_id')->constrained('ordenes_servicio')->cascadeOnDelete();
    $table->string('ruta');
    $table->enum('tipo', ['antes', 'despues'])->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fotos_servicio');
    }
};
