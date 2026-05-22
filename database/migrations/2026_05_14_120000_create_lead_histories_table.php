<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_key', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'changed_at']);
            $table->index(['event_key', 'status_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_histories');
    }
};
