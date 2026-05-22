<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_equipos', function (Blueprint $table): void {
            $table->foreignId('producto_unidad_id')->nullable()->constrained('producto_unidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_equipos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('producto_unidad_id');
        });
    }
};
