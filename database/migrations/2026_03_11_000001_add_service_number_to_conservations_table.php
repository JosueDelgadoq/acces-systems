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
        Schema::table('conservations', function (Blueprint $table) {
            $table->integer('current_service_number')->nullable()->default(1);
            $table->integer('total_services')->nullable()->default(12);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conservations', function (Blueprint $table) {
            $table->dropColumn(['current_service_number', 'total_services']);
        });
    }
};

