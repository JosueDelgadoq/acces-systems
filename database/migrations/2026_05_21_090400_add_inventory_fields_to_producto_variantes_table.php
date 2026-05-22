<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_variantes', function (Blueprint $table): void {
            $table->string('variant_code')->nullable()->unique();
            $table->string('subtype')->nullable();
            $table->json('attributes_json')->nullable();
        });

        $variants = DB::table('producto_variantes')->select('id')->get();

        foreach ($variants as $variant) {
            DB::table('producto_variantes')
                ->where('id', $variant->id)
                ->update([
                    'variant_code' => 'PV-' . str_pad((string) $variant->id, 5, '0', STR_PAD_LEFT),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('producto_variantes', function (Blueprint $table): void {
            $table->dropUnique('producto_variantes_variant_code_unique');
            $table->dropColumn(['variant_code', 'subtype', 'attributes_json']);
        });
    }
};
