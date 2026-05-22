<?php

namespace App\Filament\Resources\TechnicalBudgets;

use App\Filament\Concerns\HasPermissionControlledResource;
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
use UnitEnum;

class TechnicalBudgetResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = TechnicalBudget::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Presupuestos';

    protected static ?string $modelLabel = 'Presupuesto';

    protected static ?string $pluralModelLabel = 'Presupuestos';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string | UnitEnum | null $navigationGroup = 'BLOQUE C - Control';

    protected static ?string $navigationPermission = 'technical_budget.view';

    protected static ?string $viewAnyPermission = 'technical_budget.view';

    protected static ?string $createPermission = 'technical_budget.create';

    protected static ?string $updatePermission = 'technical_budget.update';

    protected static ?string $deletePermission = 'technical_budget.delete';

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
        return [];
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
