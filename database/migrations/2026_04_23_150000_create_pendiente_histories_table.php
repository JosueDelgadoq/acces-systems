<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendiente_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendiente_id')->constrained('pendientes')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('observation')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'changed_at']);
            $table->index(['pendiente_id', 'changed_at']);
        });

        DB::table('pendientes')
            ->select(['id', 'client_id', 'user_id', 'status', 'notes', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($pendientes): void {
                $rows = [];

                foreach ($pendientes as $pendiente) {
                    $timestamp = $pendiente->updated_at ?? $pendiente->created_at ?? now();

                    $rows[] = [
                        'pendiente_id' => $pendiente->id,
                        'client_id' => $pendiente->client_id,
                        'user_id' => $pendiente->user_id,
                        'status_from' => null,
                        'status_to' => $pendiente->status ?: 'pending',
                        'observation' => $pendiente->notes,
                        'changed_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                if ($rows !== []) {
                    DB::table('pendiente_histories')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendiente_histories');
    }
};
