<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_unidad_assignments', function (Blueprint $table): void {
            $table->string('holder_type')->nullable();
            $table->unsignedBigInteger('holder_id')->nullable();
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('client_equipo_id')->nullable()->constrained('client_equipos')->nullOnDelete();
            $table->timestamp('installed_at')->nullable();

            $table->index(['holder_type', 'holder_id'], 'producto_unidad_assignments_holder_idx');
            $table->index(['context_type', 'context_id'], 'producto_unidad_assignments_context_idx');
        });

        foreach (DB::table('producto_unidad_assignments')->select('id', 'pendiente_id', 'service_visit_id', 'technician_id', 'status', 'assigned_at', 'resolved_at')->get() as $assignment) {
            DB::table('producto_unidad_assignments')
                ->where('id', $assignment->id)
                ->update([
                    'holder_type' => $assignment->technician_id ? App\Models\User::class : null,
                    'holder_id' => $assignment->technician_id,
                    'context_type' => $assignment->service_visit_id
                        ? App\Models\ServiceVisit::class
                        : ($assignment->pendiente_id ? App\Models\Pendiente::class : null),
                    'context_id' => $assignment->service_visit_id ?: $assignment->pendiente_id,
                    'installed_at' => $assignment->status === 'installed'
                        ? ($assignment->resolved_at ?: $assignment->assigned_at)
                        : null,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('producto_unidad_assignments', function (Blueprint $table): void {
            $table->dropIndex('producto_unidad_assignments_holder_idx');
            $table->dropIndex('producto_unidad_assignments_context_idx');
            $table->dropConstrainedForeignId('client_id');
            $table->dropConstrainedForeignId('client_equipo_id');
            $table->dropColumn([
                'holder_type',
                'holder_id',
                'context_type',
                'context_id',
                'installed_at',
            ]);
        });
    }
};
