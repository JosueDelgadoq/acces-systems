<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samu_event_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('samu_event_id')
                ->constrained('samu_events')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('note');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['samu_event_id', 'created_at'], 'samu_event_notes_event_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samu_event_notes');
    }
};
