<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('used_equipment_parts', function (Blueprint $table) {
    $table->id();

    $table->foreignId('used_equipment_id')->constrained('used_equipment')->cascadeOnDelete();
    $table->foreignId('repuesto_id')->constrained('used_equipment')->cascadeOnDelete();

    $table->integer('quantity')->default(1);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('used_equipment_parts');
    }
};
