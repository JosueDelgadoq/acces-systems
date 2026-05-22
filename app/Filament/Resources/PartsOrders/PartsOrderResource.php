<?php

namespace App\Filament\Resources\PartsOrders;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\PartsOrders\Pages\CreatePartsOrder;
use App\Filament\Resources\PartsOrders\Pages\EditPartsOrder;
use App\Filament\Resources\PartsOrders\Pages\ListPartsOrders;
use App\Filament\Resources\PartsOrders\Schemas\PartsOrderForm;
use App\Filament\Resources\PartsOrders\Tables\PartsOrdersTable;
use App\Models\PartsOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PartsOrderResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = PartsOrder::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Repuestos';

    protected static ?string $modelLabel = 'Pedido de repuesto';

    protected static ?string $pluralModelLabel = 'Pedidos de repuestos';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'part_name';

    protected static string | UnitEnum | null $navigationGroup = 'BLOQUE B - Operaciones';

    protected static ?string $navigationPermission = 'parts_order.view';

    protected static ?string $viewAnyPermission = 'parts_order.view';

    protected static ?string $createPermission = 'parts_order.create';

    protected static ?string $updatePermission = 'parts_order.update';

    protected static ?string $deletePermission = 'parts_order.delete';

    public static function form(Schema $schema): Schema
    {
        return PartsOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartsOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartsOrders::route('/'),
            'create' => CreatePartsOrder::route('/create'),
            'edit' => EditPartsOrder::route('/{record}/edit'),
        ];
    }
}
