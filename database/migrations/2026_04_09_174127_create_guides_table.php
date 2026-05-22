<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();

            $table->string('type'); // interior, exterior, rebatible
            $table->integer('boxes')->default(0); // cantidad de cajas
            $table->decimal('meters_per_box', 8, 2)->default(4.80);
            $table->decimal('total_meters', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};