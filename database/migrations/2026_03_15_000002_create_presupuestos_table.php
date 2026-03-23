<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('producto_1')->nullable();
            $table->decimal('precio_1', 10, 2)->nullable();
            $table->string('producto_2')->nullable();
            $table->decimal('precio_2', 10, 2)->nullable();
            $table->string('producto_3')->nullable();
            $table->decimal('precio_3', 10, 2)->nullable();
            $table->string('producto_4')->nullable();
            $table->decimal('precio_4', 10, 2)->nullable();
            $table->decimal('presupuesto_definitivo', 10, 2)->nullable();
            $table->date('fecha_envio');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};

