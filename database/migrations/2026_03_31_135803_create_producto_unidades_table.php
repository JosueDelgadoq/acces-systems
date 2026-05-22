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
 Schema::create('producto_unidades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('producto_variante_id')->constrained()->cascadeOnDelete();
    $table->string('codigo_barra')->unique();
    $table->string('estado')->default('disponible');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_unidades');
    }
};
