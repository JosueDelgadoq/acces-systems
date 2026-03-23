<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->date('fecha_contacto');
            $table->foreignId('comercial_id')->constrained('users')->onDelete('cascade');
            $table->enum('medio_contacto', [
                'whatsapp',
                'llamada_anura_ip',
                'mail',
                'visita',
                'videollamada'
            ]);
            $table->text('resultado');
            $table->text('proxima_accion');
            $table->date('fecha_proxima_accion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};

