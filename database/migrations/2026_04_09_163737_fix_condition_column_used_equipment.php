<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 👇 eliminamos la columna problemática
        Schema::table('used_equipment', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }

    public function down(): void
    {
        Schema::table('used_equipment', function (Blueprint $table) {
            $table->string('condition')->nullable();
        });
    }
};