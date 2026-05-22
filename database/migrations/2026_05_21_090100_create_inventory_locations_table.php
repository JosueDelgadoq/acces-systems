<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('warehouse');
            $table->foreignId('parent_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('inventory_locations')->insert([
            'name' => 'Depósito general',
            'code' => 'DEPOSITO-GENERAL',
            'type' => 'warehouse',
            'notes' => 'Ubicación raíz para stock interno.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_locations');
    }
};
