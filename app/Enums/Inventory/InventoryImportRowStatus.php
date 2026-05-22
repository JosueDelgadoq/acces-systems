<?php

namespace App\Enums\Inventory;

enum InventoryImportRowStatus: string
{
    case PENDING = 'pending';
    case PREVIEW = 'preview';
    case IMPORTED = 'imported';
    case SKIPPED = 'skipped';
    case CONFLICT = 'conflict';
    case ERROR = 'error';
    case ROLLED_BACK = 'rolled_back';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PREVIEW => 'Previsualizada',
            self::IMPORTED => 'Importada',
            self::SKIPPED => 'Omitida',
            self::CONFLICT => 'Conflicto',
            self::ERROR => 'Error',
            self::ROLLED_BACK => 'Rollback',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
