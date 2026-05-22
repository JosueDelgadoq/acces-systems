<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samu_events', function (Blueprint $table): void {
            $table->string('classification')->nullable()->after('pipeline_stage')->index();
            $table->text('classification_reason')->nullable()->after('classification');
            $table->foreignId('claim_id')->nullable()->after('client_id')->index();
            $table->foreignId('pendiente_id')->nullable()->after('claim_id')->index();
        });

        Schema::table('samu_events', function (Blueprint $table): void {
            if (Schema::hasTable('claims')) {
                $table->foreign('claim_id')->references('id')->on('claims')->nullOnDelete();
            }

            if (Schema::hasTable('pendientes')) {
                $table->foreign('pendiente_id')->references('id')->on('pendientes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('samu_events', function (Blueprint $table): void {
            if (Schema::hasTable('claims')) {
                $table->dropForeign(['claim_id']);
            }

            if (Schema::hasTable('pendientes')) {
                $table->dropForeign(['pendiente_id']);
            }

            $table->dropColumn([
                'classification',
                'classification_reason',
                'claim_id',
                'pendiente_id',
            ]);
        });
    }
};
