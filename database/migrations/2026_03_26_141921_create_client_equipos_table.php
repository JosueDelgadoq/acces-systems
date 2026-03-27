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
Schema::create('client_equipos', function (Blueprint $table) {
    $table->id();

    $table->foreignId('client_id')->constrained()->cascadeOnDelete();
    $table->foreignId('equipo_id')->constrained()->cascadeOnDelete();

    $table->string('serie')->nullable();
    $table->string('ubicacion')->nullable();

    $table->string('estado')->default('activo');

    $table->date('fecha_instalacion')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_equipos');
    }
};
