<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_disk')->default('local');
            $table->string('status')->default('draft');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'inventory_import_batches_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_import_batches');
    }
};
