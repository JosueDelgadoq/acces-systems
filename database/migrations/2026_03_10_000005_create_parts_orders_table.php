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
        Schema::create('parts_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('technician_id')->nullable()->constrained('users');

            $table->string('part_name');

            $table->string('supplier')->nullable();

            $table->decimal('estimated_cost', 10, 2)->nullable();

            $table->enum('status', [
                'pendiente_cotizacion',
                'cotizado',
                'aprobado',
                'comprado',
                'recibido',
                'entregado_tecnico'
            ])->default('pendiente_cotizacion');

            $table->date('purchase_date')->nullable();

            $table->date('arrival_date')->nullable();

            $table->string('invoice')->nullable();

            $table->text('observations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts_orders');
    }
};

