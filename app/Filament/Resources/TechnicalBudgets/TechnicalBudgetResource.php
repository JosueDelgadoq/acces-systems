<?php

namespace App\Filament\Resources\TechnicalBudgets;

use App\Filament\Resources\TechnicalBudgets\Pages\CreateTechnicalBudget;
use App\Filament\Resources\TechnicalBudgets\Pages\EditTechnicalBudget;
use App\Filament\Resources\TechnicalBudgets\Pages\ListTechnicalBudgets;
use App\Filament\Resources\TechnicalBudgets\Schemas\TechnicalBudgetForm;
use App\Filament\Resources\TechnicalBudgets\Tables\TechnicalBudgetsTable;
use App\Models\TechnicalBudget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TechnicalBudgetResource extends Resource
{
    protected static ?string $model = TechnicalBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Presupuestos';

    protected static ?string $modelLabel = 'Presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TechnicalBudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TechnicalBudgetsTable::configure($table);
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
            'index' => ListTechnicalBudgets::route('/'),
            'create' => CreateTechnicalBudget::route('/create'),
            'edit' => EditTechnicalBudget::route('/{record}/edit'),
        ];
    }
}

