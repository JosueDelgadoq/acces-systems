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
    Schema::create('producto_variantes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('producto_id')
              ->constrained()
              ->cascadeOnDelete();

        $table->string('codigo_barra')->unique();

        $table->enum('estado', ['nuevo', 'usado'])->nullable();
        $table->enum('uso', ['interior', 'exterior'])->nullable();
        $table->enum('lado', ['izquierdo', 'derecho'])->nullable();
        $table->string('modelo')->nullable(); // AS30, AS32

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('producto_variantes');
}
};
