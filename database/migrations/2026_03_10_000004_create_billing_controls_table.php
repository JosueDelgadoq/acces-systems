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
        Schema::create('billing_controls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('service_description');

            $table->decimal('amount', 10, 2);

            $table->string('invoice_number')->nullable();

            $table->date('invoice_date')->nullable();

            $table->boolean('invoiced')->default(false);

            $table->boolean('paid')->default(false);

            $table->date('payment_date')->nullable();

            $table->text('observations')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_controls');
    }
};

