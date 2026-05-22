<?php

namespace App\Filament\Resources\BillingControls;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\BillingControls\Pages\CreateBillingControl;
use App\Filament\Resources\BillingControls\Pages\EditBillingControl;
use App\Filament\Resources\BillingControls\Pages\ListBillingControls;
use App\Filament\Resources\BillingControls\Schemas\BillingControlForm;
use App\Filament\Resources\BillingControls\Tables\BillingControlsTable;
use App\Models\BillingControl;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BillingControlResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = BillingControl::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Facturacion';

    protected static ?string $modelLabel = 'Control de facturacion';

    protected static ?string $pluralModelLabel = 'Controles de facturacion';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'service_description';

    protected static string | UnitEnum | null $navigationGroup = 'BLOQUE C - Control';

    protected static ?string $navigationPermission = 'billing_control.view';

    protected static ?string $viewAnyPermission = 'billing_control.view';

    protected static ?string $createPermission = 'billing_control.create';

    protected static ?string $updatePermission = 'billing_control.update';

    protected static ?string $deletePermission = 'billing_control.delete';

    public static function form(Schema $schema): Schema
    {
        return BillingControlForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingControlsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingControls::route('/'),
            'create' => CreateBillingControl::route('/create'),
            'edit' => EditBillingControl::route('/{record}/edit'),
        ];
    }
}
