<?php

namespace App\Filament\Resources\ProductoVariantes;

use App\Filament\Resources\ProductoVariantes\Pages\CreateProductoVariante;
use App\Filament\Resources\ProductoVariantes\Pages\EditProductoVariante;
use App\Filament\Resources\ProductoVariantes\Pages\ListProductoVariantes;
use App\Filament\Resources\ProductoVariantes\Schemas\ProductoVarianteForm;
use App\Filament\Resources\ProductoVariantes\Tables\ProductoVariantesTable;
use App\Models\ProductoVariante;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductoVarianteResource extends Resource
{
    protected static ?string $model = ProductoVariante::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Variantes';

    protected static ?string $recordTitleAttribute = 'inventory_label';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ProductoVarianteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductoVariantesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return InventarioPermissions::view() || InventarioPermissions::viewAll();
    }

    public static function canViewAny(): bool
    {
        return InventarioPermissions::view() || InventarioPermissions::viewAll();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canCreate(): bool
    {
        return InventarioPermissions::create();
    }

    public static function canEdit($record): bool
    {
        return InventarioPermissions::update();
    }

    public static function canDelete($record): bool
    {
        return InventarioPermissions::delete();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductoVariantes::route('/'),
            'create' => CreateProductoVariante::route('/create'),
            'edit' => EditProductoVariante::route('/{record}/edit'),
        ];
    }
}
