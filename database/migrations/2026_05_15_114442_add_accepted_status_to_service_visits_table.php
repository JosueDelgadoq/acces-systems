<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE service_visits
            MODIFY status ENUM('pending', 'accepted', 'in_progress', 'completed')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE service_visits
            MODIFY status ENUM('pending', 'in_progress', 'completed')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
