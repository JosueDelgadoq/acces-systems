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
        Schema::create('habilitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('equipment');

            $table->enum('status', [
                'pendiente',
                'documentacion',
                'enviado_gestor',
                'en_tramite',
                'aprobado',
                'rechazado',
                'archivado'
            ])->default('pendiente');

            $table->boolean('doc_completa')->default(false);

            $table->date('fecha_envio_gestor')->nullable();
            $table->date('fecha_presentacion')->nullable();
            $table->date('proxima_gestion')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habilitations');
    }
};

