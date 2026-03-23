<?php

namespace App\Filament\Resources\Presupuestos;

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

class PresupuestoResource extends Resource
{
    protected static ?string $model = Presupuesto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

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
        return [
            //
        ];
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
