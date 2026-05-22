<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('used_equipment', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('type');
            $table->string('ingress_condition', 10)->nullable()->after('status');
            $table->string('warehouse_zone')->nullable()->after('ingress_condition');
            $table->string('location')->nullable()->after('warehouse_zone');
        });

        DB::table('used_equipment')->where('status', 'available')->update(['status' => 'completa']);
        DB::table('used_equipment')->where('status', 'assigned')->update(['status' => 'a_revisar']);
        DB::table('used_equipment')->where('status', 'no_disponible')->update(['status' => 'repuestos']);
        DB::table('used_equipment')->where('status', 'installed')->update(['status' => 'vendida']);

        DB::table('used_equipment')->where('status', 'completa')->whereNull('warehouse_zone')->update(['warehouse_zone' => 'verde']);
        DB::table('used_equipment')->where('status', 'a_revisar')->whereNull('warehouse_zone')->update(['warehouse_zone' => 'amarilla']);
        DB::table('used_equipment')->where('status', 'repuestos')->whereNull('warehouse_zone')->update(['warehouse_zone' => 'roja']);
    }

    public function down(): void
    {
        DB::table('used_equipment')->where('status', 'completa')->update(['status' => 'available']);
        DB::table('used_equipment')->where('status', 'a_revisar')->update(['status' => 'assigned']);
        DB::table('used_equipment')->where('status', 'repuestos')->update(['status' => 'no_disponible']);
        DB::table('used_equipment')->where('status', 'vendida')->update(['status' => 'installed']);
        DB::table('used_equipment')->update(['warehouse_zone' => null]);

        Schema::table('used_equipment', function (Blueprint $table) {
            $table->dropColumn(['brand', 'ingress_condition', 'warehouse_zone', 'location']);
        });
    }
};
