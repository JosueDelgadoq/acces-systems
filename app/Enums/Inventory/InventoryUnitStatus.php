<?php

namespace App\Enums\Inventory;

enum InventoryUnitStatus: string
{
    case AVAILABLE = 'disponible';
    case RESERVED = 'reservado';
    case ASSIGNED = 'asignado';
    case INSTALLED = 'instalado';
    case SOLD = 'vendido';
    case REPAIR = 'reparacion';
    case RETIRED = 'baja';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
            self::RESERVED => 'Reservado',
            self::ASSIGNED => 'Asignado',
            self::INSTALLED => 'Instalado',
            self::SOLD => 'Vendido',
            self::REPAIR => 'En reparación',
            self::RETIRED => 'Baja',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
