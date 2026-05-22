<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_unidad_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_unidad_id')
                ->constrained('producto_unidades')
                ->cascadeOnDelete();
            $table->foreignId('pendiente_id')
                ->nullable()
                ->constrained('pendientes')
                ->nullOnDelete();
            $table->foreignId('service_visit_id')
                ->nullable()
                ->constrained('service_visits')
                ->nullOnDelete();
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('released_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status')->default('reserved');
            $table->text('notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['producto_unidad_id', 'status'], 'producto_unidad_assignments_unit_status_idx');
            $table->index(['pendiente_id', 'status'], 'producto_unidad_assignments_pending_status_idx');
            $table->index(['service_visit_id', 'status'], 'producto_unidad_assignments_visit_status_idx');
            $table->index(['technician_id', 'status'], 'producto_unidad_assignments_technician_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_unidad_assignments');
    }
};
