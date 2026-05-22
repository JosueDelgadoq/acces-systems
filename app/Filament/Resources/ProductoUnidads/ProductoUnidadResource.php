<?php

namespace App\Filament\Resources\ProductoUnidads;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\ProductoUnidads\Pages\CreateProductoUnidad;
use App\Filament\Resources\ProductoUnidads\Pages\EditProductoUnidad;
use App\Filament\Resources\ProductoUnidads\Pages\ListProductoUnidads;
use App\Filament\Resources\ProductoUnidads\Schemas\ProductoUnidadForm;
use App\Filament\Resources\ProductoUnidads\Tables\ProductoUnidadsTable;
use App\Models\ProductoUnidad;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductoUnidadResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = ProductoUnidad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Unidades';

    protected static ?string $recordTitleAttribute = 'codigo_barra';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    public static function form(Schema $schema): Schema
    {
        return ProductoUnidadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductoUnidadsTable::table($table);
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
            'index' => ListProductoUnidads::route('/'),
            'create' => CreateProductoUnidad::route('/create'),
            'edit' => EditProductoUnidad::route('/{record}/edit'),
        ];
    }
}
