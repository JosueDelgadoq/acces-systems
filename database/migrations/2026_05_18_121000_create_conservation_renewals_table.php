<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conservation_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('renewed_at');
            $table->unsignedInteger('previous_cycle_number');
            $table->unsignedInteger('new_cycle_number');
            $table->date('previous_start_date')->nullable();
            $table->date('previous_expiration_date')->nullable();
            $table->string('previous_frequency')->nullable();
            $table->unsignedInteger('previous_total_services')->nullable();
            $table->unsignedInteger('previous_completed_services')->nullable();
            $table->date('previous_last_service_date')->nullable();
            $table->date('new_start_date');
            $table->date('new_expiration_date');
            $table->string('new_frequency');
            $table->unsignedInteger('new_total_services');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['conservation_id', 'renewed_at'], 'conservation_renewals_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conservation_renewals');
    }
};
