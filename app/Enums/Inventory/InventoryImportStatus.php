<?php

namespace App\Enums\Inventory;

enum InventoryImportStatus: string
{
    case DRAFT = 'draft';
    case PREVIEWED = 'previewed';
    case PREVIEWED_WITH_ERRORS = 'previewed_with_errors';
    case IMPORTED = 'imported';
    case IMPORT_FAILED = 'import_failed';
    case ROLLED_BACK = 'rolled_back';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PREVIEWED => 'Previsualizada',
            self::PREVIEWED_WITH_ERRORS => 'Previsualizada con errores',
            self::IMPORTED => 'Importada',
            self::IMPORT_FAILED => 'Importación fallida',
            self::ROLLED_BACK => 'Rollback aplicado',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
