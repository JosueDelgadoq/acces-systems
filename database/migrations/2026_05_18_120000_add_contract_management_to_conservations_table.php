<?php

use App\Models\Conservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conservations', function (Blueprint $table) {
            $table->unsignedInteger('contract_cycle_number')->default(1)->after('total_services');
            $table->string('contract_status')->default(Conservation::STATUS_ACTIVE)->after('frequency');
            $table->date('renewal_required_at')->nullable()->after('completed_at');
            $table->date('next_service_date')->nullable()->change();
            $table->index(['contract_status', 'expiration_date'], 'conservations_status_expiration_idx');
        });

        DB::table('conservations')
            ->orderBy('id')
            ->get()
            ->each(function (object $row): void {
                $totalServices = max(1, (int) ($row->total_services ?: 12));
                $currentServiceNumber = max(1, (int) ($row->current_service_number ?: 1));
                $contractStatus = Conservation::STATUS_ACTIVE;
                $renewalRequiredAt = null;
                $nextServiceDate = $row->next_service_date;
                $completedAt = $row->completed_at;

                if ($currentServiceNumber > $totalServices) {
                    $currentServiceNumber = $totalServices;
                    $contractStatus = Conservation::STATUS_RENEWAL_REQUIRED;
                    $renewalRequiredAt = $row->last_service_date ?: $row->updated_at;
                    $nextServiceDate = null;
                    $completedAt = $row->completed_at ?: $row->last_service_date;
                }

                DB::table('conservations')
                    ->where('id', $row->id)
                    ->update([
                        'total_services' => $totalServices,
                        'current_service_number' => $currentServiceNumber,
                        'contract_cycle_number' => $row->contract_cycle_number ?: 1,
                        'contract_status' => $contractStatus,
                        'renewal_required_at' => $renewalRequiredAt,
                        'next_service_date' => $nextServiceDate,
                        'completed_at' => $completedAt,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('conservations', function (Blueprint $table) {
            $table->dropIndex('conservations_status_expiration_idx');
            $table->dropColumn([
                'contract_cycle_number',
                'contract_status',
                'renewal_required_at',
            ]);
        });
    }
};
