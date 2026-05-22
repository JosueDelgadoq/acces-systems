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
        Schema::create('movimientos_repuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repuesto_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->integer('cantidad');
            $table->string('motivo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_repuestos');
    }
};
