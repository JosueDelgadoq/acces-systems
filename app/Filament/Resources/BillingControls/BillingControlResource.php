<?php

namespace App\Filament\Resources\BillingControls;

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

class BillingControlResource extends Resource
{
    protected static ?string $model = BillingControl::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Facturación';

    protected static ?string $modelLabel = 'Control de Facturación';

    protected static ?string $pluralModelLabel = 'Control de Facturación';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'service_description';

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
        return [
            //
        ];
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

