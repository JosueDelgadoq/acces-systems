<?php

namespace App\Filament\Resources\CommercialGoals;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\CommercialGoals\Pages\CreateCommercialGoal;
use App\Filament\Resources\CommercialGoals\Pages\EditCommercialGoal;
use App\Filament\Resources\CommercialGoals\Pages\ListCommercialGoals;
use App\Filament\Resources\CommercialGoals\Schemas\CommercialGoalForm;
use App\Filament\Resources\CommercialGoals\Tables\CommercialGoalsTable;
use App\Models\CommercialGoal;
use App\Support\Modules\LeadPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CommercialGoalResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = CommercialGoal::class;

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Objetivos';

    protected static ?string $modelLabel = 'Objetivo comercial';

    protected static ?string $pluralModelLabel = 'Objetivos comerciales';

    protected static ?string $navigationPermission = LeadPermissions::ASSIGN;

    protected static ?string $viewAnyPermission = LeadPermissions::ASSIGN;

    protected static ?string $createPermission = LeadPermissions::ASSIGN;

    protected static ?string $updatePermission = LeadPermissions::ASSIGN;

    protected static ?string $deletePermission = LeadPermissions::ASSIGN;

    public static function form(Schema $schema): Schema
    {
        return CommercialGoalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommercialGoalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommercialGoals::route('/'),
            'create' => CreateCommercialGoal::route('/create'),
            'edit' => EditCommercialGoal::route('/{record}/edit'),
        ];
    }
}
