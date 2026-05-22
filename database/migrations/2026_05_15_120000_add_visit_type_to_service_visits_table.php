<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_visits', function (Blueprint $table) {
            $table->string('visit_type')
                ->default('technical_service');

            $table->index(['technician_id', 'status'], 'service_visits_technician_status_index');
            $table->index(['pendiente_id', 'status'], 'service_visits_pendiente_status_index');
            $table->index('visit_type', 'service_visits_visit_type_index');
        });

        $pendienteTypes = DB::table('pendientes')
            ->pluck('type', 'id');

        DB::table('service_visits')
            ->select(['id', 'pendiente_id'])
            ->orderBy('id')
            ->chunkById(100, function ($visits) use ($pendienteTypes): void {
                foreach ($visits as $visit) {
                    $pendienteType = $visit->pendiente_id
                        ? $pendienteTypes->get($visit->pendiente_id)
                        : null;

                    DB::table('service_visits')
                        ->where('id', $visit->id)
                        ->update([
                            'visit_type' => match ($pendienteType) {
                                'instalacion' => 'installation',
                                'diagnostico', 'presupuesto' => 'diagnosis',
                                default => 'technical_service',
                            },
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('service_visits', function (Blueprint $table) {
            $table->dropIndex('service_visits_technician_status_index');
            $table->dropIndex('service_visits_pendiente_status_index');
            $table->dropIndex('service_visits_visit_type_index');
            $table->dropColumn('visit_type');
        });
    }
};
