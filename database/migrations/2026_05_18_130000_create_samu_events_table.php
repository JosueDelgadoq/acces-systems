<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samu_events', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->nullable()->index();
            $table->string('source')->default('samu')->index();
            $table->string('event_type')->nullable()->index();
            $table->foreignId('lead_id')->nullable()->index();
            $table->foreignId('client_id')->nullable()->index();
            $table->json('payload');
            $table->text('summary')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('interest_level')->nullable()->index();
            $table->text('next_step')->nullable();
            $table->string('pipeline_stage')->nullable()->index();
            $table->json('objections')->nullable();
            $table->json('tasks_detected')->nullable();
            $table->boolean('processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            if (Schema::hasTable('leads')) {
                $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            }

            if (Schema::hasTable('clients')) {
                $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samu_events');
    }
};
