<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('used_equipment', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('missing_parts');
        });
    }

    public function down(): void
    {
        Schema::table('used_equipment', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
