<?php

namespace App\Filament\Resources\EquipmentDeliveries;

use App\Filament\Resources\EquipmentDeliveries\Pages\CreateEquipmentDelivery;
use App\Filament\Resources\EquipmentDeliveries\Pages\EditEquipmentDelivery;
use App\Filament\Resources\EquipmentDeliveries\Pages\ListEquipmentDeliveries;
use App\Filament\Resources\EquipmentDeliveries\Schemas\EquipmentDeliveryForm;
use App\Filament\Resources\EquipmentDeliveries\Tables\EquipmentDeliveriesTable;
use App\Models\EquipmentDelivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EquipmentDeliveryResource extends Resource
{
    protected static ?string $model = EquipmentDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Logística';

    protected static ?string $modelLabel = 'Entrega de Equipo';

    protected static ?string $pluralModelLabel = 'Entregas de Equipos';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'equipment';

    public static function form(Schema $schema): Schema
    {
        return EquipmentDeliveryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentDeliveriesTable::configure($table);
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
            'index' => ListEquipmentDeliveries::route('/'),
            'create' => CreateEquipmentDelivery::route('/create'),
            'edit' => EditEquipmentDelivery::route('/{record}/edit'),
        ];
    }
}

