<?php

namespace App\Services;

use App\Models\ServiceVisit;
use App\Models\User;
use Carbon\CarbonInterface;

class TechnicianStatusResolver
{
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_TRAVELING = 'traveling';
    public const STATUS_WORKING = 'working';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_FINISHED = 'finished';

    public function resolve(
        User $user,
        ?ServiceVisit $activeVisit,
        bool $isMoving,
        ?CarbonInterface $trackedAt = null
    ): string {
        $trackedAt ??= $user->last_seen_at;

        if (! $trackedAt || $trackedAt->lt(now()->subMinutes(3))) {
            return self::STATUS_OFFLINE;
        }

        if ($user->technician_status === self::STATUS_PAUSED) {
            return self::STATUS_PAUSED;
        }

        if ($activeVisit) {
            if ($activeVisit->arrival_time && ! $activeVisit->departure_time) {
                return self::STATUS_WORKING;
            }

            if ($user->technician_status === self::STATUS_WORKING) {
                return self::STATUS_WORKING;
            }

            if ($user->technician_status === self::STATUS_TRAVELING) {
                return self::STATUS_TRAVELING;
            }

            return self::STATUS_TRAVELING;
        }

        if ($user->technician_status === self::STATUS_FINISHED && $trackedAt->gt(now()->subMinutes(20))) {
            return self::STATUS_FINISHED;
        }

        if ($user->technician_status === self::STATUS_WORKING) {
            return self::STATUS_WORKING;
        }

        if ($user->technician_status === self::STATUS_TRAVELING) {
            return self::STATUS_TRAVELING;
        }

        return $isMoving ? self::STATUS_TRAVELING : self::STATUS_AVAILABLE;
    }

    public function label(string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => 'Disponible',
            self::STATUS_TRAVELING => 'En camino',
            self::STATUS_WORKING => 'Trabajando',
            self::STATUS_PAUSED => 'Pausado',
            self::STATUS_FINISHED => 'Finalizado',
            default => 'Desconectado',
        };
    }

    public function color(string $status): string
    {
        return match ($status) {
            self::STATUS_AVAILABLE => '#2563eb',
            self::STATUS_TRAVELING => '#f97316',
            self::STATUS_WORKING => '#16a34a',
            self::STATUS_PAUSED => '#eab308',
            self::STATUS_FINISHED => '#8b5cf6',
            default => '#64748b',
        };
    }
}
