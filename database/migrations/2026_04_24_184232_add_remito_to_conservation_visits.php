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
    Schema::table('conservation_visits', function (Blueprint $table) {
        $table->string('remito', 4)->nullable()->after('notes');
    });
}

public function down(): void
{
    Schema::table('conservation_visits', function (Blueprint $table) {
        $table->dropColumn('remito');
    });
}
};
