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
    Schema::create('movimientos_stock', function (Blueprint $table) {
        $table->id();

        $table->foreignId('producto_variante_id')
              ->constrained('producto_variantes')
              ->cascadeOnDelete();

        $table->enum('tipo', ['entrada', 'salida']);
        $table->integer('cantidad');

        $table->foreignId('usuario_id')
              ->constrained('users');

        $table->text('nota')->nullable();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('movimientos_stock');
}
};
