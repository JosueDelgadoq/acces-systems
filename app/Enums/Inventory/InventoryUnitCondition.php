<?php

namespace App\Enums\Inventory;

enum InventoryUnitCondition: string
{
    case NEW = 'nuevo';
    case USED = 'usado';
    case REFURBISHED = 'reacondicionado';
    case FOR_PARTS = 'repuestos';
    case DAMAGED = 'danado';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nuevo',
            self::USED => 'Usado',
            self::REFURBISHED => 'Reacondicionado',
            self::FOR_PARTS => 'Para repuestos',
            self::DAMAGED => 'Dañado',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $condition): array => [$condition->value => $condition->label()])
            ->all();
    }
}
