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
    Schema::create('conservations', function (Blueprint $table) {
        $table->id();

        $table->foreignId('client_id')->constrained()->cascadeOnDelete();

        $table->date('start_date');
        $table->date('next_service_date');
        $table->date('expiration_date');

        $table->string('frequency')->nullable(); 
        $table->text('notes')->nullable();

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conservations');
    }
};
