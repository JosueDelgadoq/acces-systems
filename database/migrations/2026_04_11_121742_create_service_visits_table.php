<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_visits', function (Blueprint $table) {
            $table->id();

            // Relación con reclamo
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();

            // Técnico
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();

            // Estado
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            // Tiempos
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('departure_time')->nullable();

            // Fotos
            $table->string('arrival_photo')->nullable();
            $table->string('departure_photo')->nullable();

            // Informe
            $table->text('report')->nullable();

            // GPS
            $table->decimal('lat_arrival', 10, 7)->nullable();
            $table->decimal('lng_arrival', 10, 7)->nullable();
            $table->decimal('lat_departure', 10, 7)->nullable();
            $table->decimal('lng_departure', 10, 7)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_visits');
    }
};