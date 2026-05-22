<?php

namespace App\Enums\Inventory;

enum InventoryVariantSide: string
{
    case LEFT = 'izquierdo';
    case RIGHT = 'derecho';
    case BOTH = 'bilateral';
    case CENTER = 'central';

    public function label(): string
    {
        return match ($this) {
            self::LEFT => 'Izquierdo',
            self::RIGHT => 'Derecho',
            self::BOTH => 'Bilateral',
            self::CENTER => 'Central',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $side): array => [$side->value => $side->label()])
            ->all();
    }
}
