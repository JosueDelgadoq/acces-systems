<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('goal_month');
            $table->unsignedInteger('target_leads')->default(0);
            $table->unsignedInteger('target_contacts')->default(0);
            $table->unsignedInteger('target_quotes')->default(0);
            $table->unsignedInteger('target_sales')->default(0);
            $table->decimal('target_revenue', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'goal_month']);
            $table->index('goal_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_goals');
    }
};
