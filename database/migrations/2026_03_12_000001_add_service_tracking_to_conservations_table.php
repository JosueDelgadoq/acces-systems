<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conservations', function (Blueprint $table) {
            $table->date('last_service_date')->nullable()->after('next_service_date');
            $table->boolean('completed_this_month')->default(false)->after('last_service_date');
            $table->date('completed_at')->nullable()->after('completed_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('conservations', function (Blueprint $table) {
            $table->dropColumn(['last_service_date', 'completed_this_month', 'completed_at']);
        });
    }
};
