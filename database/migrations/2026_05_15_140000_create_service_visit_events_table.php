<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_visit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_visit_id')->nullable()->constrained('service_visits')->nullOnDelete();
            $table->foreignId('pendiente_id')->nullable()->constrained('pendientes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type');
            $table->text('description')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['service_visit_id', 'created_at'], 'service_visit_events_visit_created_idx');
            $table->index(['service_visit_id', 'event_type', 'created_at'], 'service_visit_events_visit_type_created_idx');
            $table->index(['pendiente_id', 'created_at'], 'service_visit_events_pending_created_idx');
            $table->index(['user_id', 'created_at'], 'service_visit_events_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_visit_events');
    }
};
