<?php

namespace App\Filament\Resources\ServiceVisits;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\ServiceVisits\Pages\EditServiceVisit;
use App\Filament\Resources\ServiceVisits\Pages\ListServiceVisits;
use App\Filament\Resources\ServiceVisits\Schemas\ServiceVisitForm;
use App\Filament\Resources\ServiceVisits\Tables\ServiceVisitTable;
use App\Models\ServiceVisit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ServiceVisitResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = ServiceVisit::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Visitas tecnicas';

    protected static ?string $modelLabel = 'Visita tecnica';

    protected static ?string $pluralModelLabel = 'Visitas tecnicas';

    protected static ?int $navigationSort = 4;

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'technical_board.view';

    protected static ?string $viewAnyPermission = 'technical_board.view';

    protected static ?string $updatePermission = 'technical_board.update';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'pendiente.client',
                'technician',
                'claim.client',
            ])
            ->when(
                request()->get('tableFilters')['pendiente_id']['value'] ?? null,
                fn (Builder $query, $id): Builder => $query->where('pendiente_id', $id),
            );
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceVisitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceVisitTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceVisits::route('/'),
            'edit' => EditServiceVisit::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
