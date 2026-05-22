<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_unidad_movements', function (Blueprint $table): void {
            $table->foreignId('from_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->timestamp('performed_at')->nullable();
        });

        DB::table('producto_unidad_movements')
            ->whereNull('performed_at')
            ->update(['performed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('producto_unidad_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('from_location_id');
            $table->dropConstrainedForeignId('to_location_id');
            $table->dropColumn('performed_at');
        });
    }
};
