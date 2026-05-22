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
 Schema::create('repuestos', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->string('codigo')->unique()->nullable();
    $table->text('descripcion')->nullable();
    $table->integer('stock_actual')->default(0);
    $table->integer('stock_minimo')->default(0);
    $table->decimal('precio_compra', 10, 2)->nullable();
    $table->decimal('precio_venta', 10, 2)->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repuestos');
    }
};
