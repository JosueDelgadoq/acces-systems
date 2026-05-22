<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conservation_visits', function (Blueprint $table) {
            $table->unsignedInteger('service_number')->nullable()->after('date');
            $table->unsignedInteger('contract_cycle_number')->default(1)->after('service_number');
            $table->unsignedInteger('contract_total_services')->nullable()->after('contract_cycle_number');
            $table->index(['conservation_id', 'contract_cycle_number', 'service_number'], 'conservation_visits_cycle_service_idx');
        });

        $visitsByConservation = DB::table('conservation_visits')
            ->select('conservation_id')
            ->distinct()
            ->pluck('conservation_id');

        foreach ($visitsByConservation as $conservationId) {
            $conservation = DB::table('conservations')
                ->select('id', 'total_services')
                ->where('id', $conservationId)
                ->first();

            $serviceNumber = 1;

            DB::table('conservation_visits')
                ->where('conservation_id', $conservationId)
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->each(function (object $visit) use (&$serviceNumber, $conservation): void {
                    DB::table('conservation_visits')
                        ->where('id', $visit->id)
                        ->update([
                            'service_number' => $serviceNumber,
                            'contract_cycle_number' => 1,
                            'contract_total_services' => $conservation?->total_services ?: null,
                        ]);

                    $serviceNumber++;
                });
        }
    }

    public function down(): void
    {
        Schema::table('conservation_visits', function (Blueprint $table) {
            $table->dropIndex('conservation_visits_cycle_service_idx');
            $table->dropColumn([
                'service_number',
                'contract_cycle_number',
                'contract_total_services',
            ]);
        });
    }
};
