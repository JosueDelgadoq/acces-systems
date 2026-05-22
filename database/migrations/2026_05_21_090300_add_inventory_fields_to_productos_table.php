<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('tracking_mode')->nullable()->default('unitized');
            $table->string('brand')->nullable();
        });

        $categories = DB::table('inventory_categories')->pluck('id', 'slug');
        $products = DB::table('productos')->select('id', 'nombre', 'tipo')->get();

        foreach ($products as $product) {
            $slug = match (true) {
                Str::of((string) $product->nombre)->lower()->contains('oruga') => 'oruga',
                Str::of((string) $product->nombre)->lower()->contains('aleron') => 'aleron',
                filled($product->tipo) && $categories->has($product->tipo) => $product->tipo,
                default => 'componente',
            };

            DB::table('productos')
                ->where('id', $product->id)
                ->update([
                    'category_id' => $categories[$slug] ?? null,
                    'tracking_mode' => 'unitized',
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
            $table->dropUnique('productos_sku_unique');
            $table->dropColumn(['sku', 'tracking_mode', 'brand']);
        });
    }
};
