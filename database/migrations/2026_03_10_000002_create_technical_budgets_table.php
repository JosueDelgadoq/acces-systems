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
        Schema::create('technical_budgets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('status', [
                'nuevo',
                'cotizado',
                'enviado',
                'aprobado',
                'rechazado',
                'visita_coordinada',
                'cerrado'
            ])->default('nuevo');

            $table->decimal('amount', 12, 2)->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->date('approval_date')->nullable();
            $table->date('scheduled_visit')->nullable();

            $table->string('service_remito')->nullable();

            $table->text('observations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_budgets');
    }
};

