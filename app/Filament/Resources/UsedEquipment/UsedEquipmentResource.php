<?php

namespace App\Filament\Resources\UsedEquipment;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\UsedEquipment\Pages;
use App\Filament\Resources\UsedEquipment\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\UsedEquipment\Schemas\UsedEquipmentForm;
use App\Filament\Resources\UsedEquipment\Tables\UsedEquipmentTable;
use App\Models\UsedEquipment;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class UsedEquipmentResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = UsedEquipment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Stock usado';

    protected static ?string $modelLabel = 'Unidad usada';

    protected static ?string $pluralModelLabel = 'Stock usado';

    protected static ?int $navigationSort = 3;

    protected static UnitEnum|string|null $navigationGroup = 'Stock';

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    public static function form(Schema $schema): Schema
    {
        return UsedEquipmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsedEquipmentTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return InventarioPermissions::view() || InventarioPermissions::viewAll();
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsedEquipment::route('/'),
            'create' => Pages\CreateUsedEquipment::route('/create'),
            'edit' => Pages\EditUsedEquipment::route('/{record}/edit'),
        ];
    }
}
