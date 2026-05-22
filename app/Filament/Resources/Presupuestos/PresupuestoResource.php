<?php

namespace App\Filament\Resources\Presupuestos;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Presupuestos\Pages\CreatePresupuesto;
use App\Filament\Resources\Presupuestos\Pages\EditPresupuesto;
use App\Filament\Resources\Presupuestos\Pages\ListPresupuestos;
use App\Filament\Resources\Presupuestos\Schemas\PresupuestoForm;
use App\Filament\Resources\Presupuestos\Tables\PresupuestosTable;
use App\Models\Presupuesto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PresupuestoResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Presupuesto::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Comercial';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationPermission = 'presupuesto.view';

    protected static ?string $viewAnyPermission = 'presupuesto.view';

    protected static ?string $createPermission = 'presupuesto.create';

    protected static ?string $updatePermission = 'presupuesto.update';

    protected static ?string $deletePermission = 'presupuesto.delete';

    public static function form(Schema $schema): Schema
    {
        return PresupuestoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresupuestosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPresupuestos::route('/'),
            'create' => CreatePresupuesto::route('/create'),
            'edit' => EditPresupuesto::route('/{record}/edit'),
        ];
    }
}
