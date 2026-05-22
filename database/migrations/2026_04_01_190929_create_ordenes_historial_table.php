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
Schema::create('ordenes_historial', function (Blueprint $table) {
    $table->id();
    $table->foreignId('orden_id')->constrained('ordenes_servicio')->cascadeOnDelete();
    $table->string('estado');
    $table->text('comentario')->nullable();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_historial');
    }
};
