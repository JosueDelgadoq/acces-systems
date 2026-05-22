<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('alert_key');
            $table->string('channel', 32);
            $table->string('target')->nullable();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('dedupe_key')->unique();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['alert_key', 'channel']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_dispatches');
    }
};
