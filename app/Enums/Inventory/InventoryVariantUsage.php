<?php

namespace App\Enums\Inventory;

enum InventoryVariantUsage: string
{
    case INTERIOR = 'interior';
    case EXTERIOR = 'exterior';
    case MIXED = 'mixto';

    public function label(): string
    {
        return match ($this) {
            self::INTERIOR => 'Interior',
            self::EXTERIOR => 'Exterior',
            self::MIXED => 'Mixto',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $usage): array => [$usage->value => $usage->label()])
            ->all();
    }
}
