<?php

namespace App\Filament\Resources\EquipmentDeliveries;

use App\Filament\Concerns\HasPermissionControlledResource;
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
use UnitEnum;

class EquipmentDeliveryResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = EquipmentDelivery::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Logistica';

    protected static ?string $modelLabel = 'Entrega de equipo';

    protected static ?string $pluralModelLabel = 'Entregas de equipos';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'equipment';

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'equipment_delivery.view';

    protected static ?string $viewAnyPermission = 'equipment_delivery.view';

    protected static ?string $createPermission = 'equipment_delivery.create';

    protected static ?string $updatePermission = 'equipment_delivery.update';

    protected static ?string $deletePermission = 'equipment_delivery.delete';

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
        return [];
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
