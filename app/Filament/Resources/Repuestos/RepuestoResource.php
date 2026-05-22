<?php

namespace App\Filament\Resources\Repuestos;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Repuestos\Pages\CreateRepuesto;
use App\Filament\Resources\Repuestos\Pages\EditRepuesto;
use App\Filament\Resources\Repuestos\Pages\ListRepuestos;
use App\Filament\Resources\Repuestos\Schemas\RepuestoForm;
use App\Filament\Resources\Repuestos\Tables\RepuestosTable;
use App\Models\Repuesto;
use App\Support\Modules\InventarioPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RepuestoResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Repuesto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationPermission = InventarioPermissions::VIEW;

    protected static ?string $viewAnyPermission = InventarioPermissions::VIEW;

    protected static ?string $createPermission = InventarioPermissions::CREATE;

    protected static ?string $updatePermission = InventarioPermissions::UPDATE;

    protected static ?string $deletePermission = InventarioPermissions::DELETE;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(RepuestoForm::schema());
    }

    public static function table(Table $table): Table
    {
        return RepuestosTable::configure($table);
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
            'index' => ListRepuestos::route('/'),
            'create' => CreateRepuesto::route('/create'),
            'edit' => EditRepuesto::route('/{record}/edit'),
        ];
    }
}
