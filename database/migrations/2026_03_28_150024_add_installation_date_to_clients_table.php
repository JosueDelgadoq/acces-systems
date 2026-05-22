<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->date('installation_date')->nullable();
    });
}

public function down(): void
{
    Schema::table('clients', function (Blueprint $table) {
        $table->dropColumn('installation_date');
    });
}
};
