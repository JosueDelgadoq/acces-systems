<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('used_equipment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('used_equipment_id')->constrained('used_equipment')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type');
            $table->text('description')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->json('parts_before')->nullable();
            $table->json('parts_after')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        $now = now();

        foreach (DB::table('used_equipment')->select('id', 'status', 'warehouse_zone', 'location', 'missing_parts', 'created_at', 'updated_at')->get() as $equipment) {
            $zone = match ($equipment->warehouse_zone) {
                'verde' => 'Zona verde',
                'amarilla' => 'Zona amarilla',
                'roja' => 'Zona roja',
                default => null,
            };

            $location = implode(' | ', array_filter([$zone, $equipment->location]));

            DB::table('used_equipment_movements')->insert([
                'used_equipment_id' => $equipment->id,
                'user_id' => null,
                'movement_type' => 'ingreso',
                'description' => 'Movimiento inicial migrado desde el stock previo.',
                'from_status' => null,
                'to_status' => $equipment->status,
                'from_location' => null,
                'to_location' => $location !== '' ? $location : null,
                'parts_before' => null,
                'parts_after' => $equipment->missing_parts,
                'meta' => null,
                'created_at' => $equipment->created_at ?? $now,
                'updated_at' => $equipment->updated_at ?? $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('used_equipment_movements');
    }
};
