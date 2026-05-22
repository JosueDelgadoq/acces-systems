<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('inventory_categories')->insert([
            ['name' => 'Sillas', 'slug' => 'silla', 'code' => 'SILLA', 'description' => 'Sillas y asientos motorizados.', 'sort_order' => 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Motores', 'slug' => 'motor', 'code' => 'MOTOR', 'description' => 'Motores y kits motrices.', 'sort_order' => 20, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Plataformas', 'slug' => 'plataforma', 'code' => 'PLAT', 'description' => 'Plataformas elevadoras.', 'sort_order' => 30, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Alerones', 'slug' => 'aleron', 'code' => 'ALERON', 'description' => 'Alerones y extensiones.', 'sort_order' => 40, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Orugas', 'slug' => 'oruga', 'code' => 'ORUGA', 'description' => 'Sistemas de oruga y traslación.', 'sort_order' => 50, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Guías', 'slug' => 'guia', 'code' => 'GUIA', 'description' => 'Guías y perfiles de movimiento.', 'sort_order' => 60, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Repuestos', 'slug' => 'repuesto', 'code' => 'REP', 'description' => 'Repuestos y consumibles.', 'sort_order' => 70, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Componentes', 'slug' => 'componente', 'code' => 'COMP', 'description' => 'Componentes varios de inventario.', 'sort_order' => 80, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_categories');
    }
};
