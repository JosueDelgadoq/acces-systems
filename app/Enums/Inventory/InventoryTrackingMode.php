<?php

namespace App\Enums\Inventory;

enum InventoryTrackingMode: string
{
    case SERIALIZED = 'serialized';
    case UNITIZED = 'unitized';
    case AGGREGATE = 'aggregate';

    public function label(): string
    {
        return match ($this) {
            self::SERIALIZED => 'Serializado',
            self::UNITIZED => 'Unidad física sin serie',
            self::AGGREGATE => 'Stock agregado',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode): array => [$mode->value => $mode->label()])
            ->all();
    }
}
