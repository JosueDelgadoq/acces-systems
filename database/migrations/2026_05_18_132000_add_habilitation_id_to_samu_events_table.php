<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samu_events', function (Blueprint $table): void {
            $table->foreignId('habilitation_id')->nullable()->after('pendiente_id')->index();
        });

        Schema::table('samu_events', function (Blueprint $table): void {
            if (Schema::hasTable('habilitations')) {
                $table->foreign('habilitation_id')->references('id')->on('habilitations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('samu_events', function (Blueprint $table): void {
            if (Schema::hasTable('habilitations')) {
                $table->dropForeign(['habilitation_id']);
            }

            $table->dropColumn('habilitation_id');
        });
    }
};
