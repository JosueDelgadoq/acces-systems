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
        Schema::create('equipment_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('equipment');

            $table->date('sale_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('installation_date')->nullable();

            $table->string('delivery_remito')->nullable();
            $table->string('installation_remito')->nullable();

            $table->boolean('signed_remito')->default(false);

            $table->enum('status', [
                'pendiente_confirmacion',
                'confirmado',
                'flete_solicitado',
                'en_camino',
                'entregado',
                'instalado'
            ])->default('pendiente_confirmacion');

            $table->text('observations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_deliveries');
    }
};

