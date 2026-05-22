<?php

namespace App\Filament\Resources\GuiaItems;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\GuiaItems\Pages\CreateGuiaItem;
use App\Filament\Resources\GuiaItems\Pages\EditGuiaItem;
use App\Filament\Resources\GuiaItems\Pages\ListGuiaItems;
use App\Filament\Resources\GuiaItems\Schemas\GuiaItemForm;
use App\Filament\Resources\GuiaItems\Tables\GuiaItemsTable;
use App\Models\GuiaItem;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GuiaItemResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = GuiaItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Guias';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return GuiaItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuiaItemsTable::configure($table);
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuiaItems::route('/'),
            'create' => CreateGuiaItem::route('/create'),
            'edit' => EditGuiaItem::route('/{record}/edit'),
        ];
    }
}
