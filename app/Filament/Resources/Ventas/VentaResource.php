<?php

namespace App\Filament\Resources\Ventas;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Ventas\Pages\CreateVenta;
use App\Filament\Resources\Ventas\Pages\EditVenta;
use App\Filament\Resources\Ventas\Pages\ListVentas;
use App\Filament\Resources\Ventas\Schemas\VentaForm;
use App\Filament\Resources\Ventas\Tables\VentasTable;
use App\Models\Venta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VentaResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Venta::class;

    protected static string | UnitEnum | null $navigationGroup = 'Comercial';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationPermission = 'venta.view';

    protected static ?string $viewAnyPermission = 'venta.view';

    protected static ?string $createPermission = 'venta.create';

    protected static ?string $updatePermission = 'venta.update';

    protected static ?string $deletePermission = 'venta.delete';

    public static function form(Schema $schema): Schema
    {
        return VentaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VentasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVentas::route('/'),
            'create' => CreateVenta::route('/create'),
            'edit' => EditVenta::route('/{record}/edit'),
        ];
    }
}
