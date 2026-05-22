<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guia_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_variante_id')
                ->constrained('producto_variantes')
                ->cascadeOnDelete();

            $table->decimal('longitud', 6, 2);

            $table->boolean('tiene_angulo')->default(false);
            $table->boolean('tiene_patas')->default(false);
            $table->boolean('rebatible')->default(false);

            $table->enum('estado', ['nuevo', 'usado'])->default('usado');

            $table->unsignedBigInteger('ubicacion_id')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guia_items');
    }
};