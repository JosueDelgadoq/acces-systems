<?php

namespace App\Services;

use App\Models\Conservation;
use App\Models\ConservationRenewal;
use App\Models\ConservationVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConservationContractService
{
    public function registerService(Conservation $conservation, array $data): ConservationVisit
    {
        return DB::transaction(function () use ($conservation, $data): ConservationVisit {
            /** @var Conservation $conservation */
            $conservation = Conservation::query()
                ->lockForUpdate()
                ->findOrFail($conservation->getKey());

            if ($conservation->isRenewalRequired()) {
                throw ValidationException::withMessages([
                    'date' => 'El contrato ya completo todas sus conservaciones y requiere renovación.',
                ]);
            }

            $serviceDate = Carbon::parse($data['date'])->startOfDay();

            if ($conservation->expiration_date && $serviceDate->isAfter($conservation->expiration_date)) {
                throw ValidationException::withMessages([
                    'date' => 'No podes registrar una conservación fuera de la vigencia del contrato.',
                ]);
            }

            $serviceNumber = max(1, min(
                (int) ($conservation->total_services ?? 1),
                (int) ($conservation->current_service_number ?? 1),
            ));

            $visit = $conservation->visits()->create([
                'date' => $serviceDate->toDateString(),
                'service_number' => $serviceNumber,
                'contract_cycle_number' => $conservation->contract_cycle_number ?? 1,
                'contract_total_services' => $conservation->total_services,
                'technician_id' => $data['technician_id'],
                'status' => 'done',
                'notes' => $data['notes'] ?? null,
                'remito' => $data['remito'],
            ]);

            $conservation->last_service_date = $serviceDate;
            $conservation->completed_this_month = $serviceDate->isSameMonth(now())
                && $serviceDate->isSameYear(now());

            if ($serviceNumber >= (int) $conservation->total_services) {
                $conservation->current_service_number = $conservation->total_services;
                $conservation->next_service_date = null;
                $conservation->completed_at = $serviceDate;
                $conservation->renewal_required_at = $serviceDate;
                $conservation->contract_status = Conservation::STATUS_RENEWAL_REQUIRED;
            } else {
                $conservation->current_service_number = $serviceNumber + 1;
                $conservation->next_service_date = Conservation::calculateNextServiceDate(
                    $serviceDate,
                    (string) $conservation->frequency,
                );
                $conservation->completed_at = null;
                $conservation->renewal_required_at = null;
                $conservation->contract_status = Conservation::STATUS_ACTIVE;
            }

            $conservation->save();

            return $visit->fresh(['conservation.client', 'technician']);
        });
    }

    public function renewContract(
        Conservation $conservation,
        array $data,
        ?User $user = null,
    ): ConservationRenewal {
        return DB::transaction(function () use ($conservation, $data, $user): ConservationRenewal {
            /** @var Conservation $conservation */
            $conservation = Conservation::query()
                ->lockForUpdate()
                ->findOrFail($conservation->getKey());

            if (! $conservation->canBeRenewed()) {
                throw ValidationException::withMessages([
                    'renewed_at' => 'El contrato todavía está activo. Renovalo cuando complete el ciclo o haya vencido.',
                ]);
            }

            $renewedAt = Carbon::parse($data['renewed_at'] ?? now())->startOfDay();
            $newStartDate = Carbon::parse($data['new_start_date'])->startOfDay();
            $newExpirationDate = Carbon::parse($data['new_expiration_date'])->startOfDay();
            $newTotalServices = max(1, (int) $data['total_services']);
            $newFrequency = (string) $data['frequency'];

            if ($newExpirationDate->isBefore($newStartDate)) {
                throw ValidationException::withMessages([
                    'new_expiration_date' => 'La vigencia nueva no puede terminar antes de que empiece.',
                ]);
            }

            $renewal = $conservation->renewals()->create([
                'renewed_by' => $user?->getKey(),
                'renewed_at' => $renewedAt->toDateString(),
                'previous_cycle_number' => $conservation->contract_cycle_number ?? 1,
                'new_cycle_number' => ($conservation->contract_cycle_number ?? 1) + 1,
                'previous_start_date' => $conservation->start_date,
                'previous_expiration_date' => $conservation->expiration_date,
                'previous_frequency' => $conservation->frequency,
                'previous_total_services' => $conservation->total_services,
                'previous_completed_services' => $conservation->completed_services_count,
                'previous_last_service_date' => $conservation->last_service_date,
                'new_start_date' => $newStartDate->toDateString(),
                'new_expiration_date' => $newExpirationDate->toDateString(),
                'new_frequency' => $newFrequency,
                'new_total_services' => $newTotalServices,
                'notes' => $data['notes'] ?? null,
            ]);

            $conservation->fill([
                'start_date' => $newStartDate->toDateString(),
                'expiration_date' => $newExpirationDate->toDateString(),
                'frequency' => $newFrequency,
                'total_services' => $newTotalServices,
                'current_service_number' => 1,
                'next_service_date' => Conservation::calculateNextServiceDate($newStartDate, $newFrequency),
                'contract_cycle_number' => ($conservation->contract_cycle_number ?? 1) + 1,
                'contract_status' => Conservation::STATUS_ACTIVE,
                'last_service_date' => null,
                'completed_this_month' => false,
                'completed_at' => null,
                'renewal_required_at' => null,
            ]);

            $conservation->save();

            return $renewal->fresh(['conservation.client', 'renewedBy']);
        });
    }
}
