<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('seguimientos', function (Blueprint $table) {
        $table->string('resultado')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('seguimientos', function (Blueprint $table) {
        $table->string('resultado')->nullable(false)->change();
    });
}
};
