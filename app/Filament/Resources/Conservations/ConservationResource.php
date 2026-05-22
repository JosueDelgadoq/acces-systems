<?php

namespace App\Filament\Resources\Conservations;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Conservations\Pages\CreateConservation;
use App\Filament\Resources\Conservations\Pages\EditConservation;
use App\Filament\Resources\Conservations\Pages\ListConservations;
use App\Filament\Resources\Conservations\Schemas\ConservationForm;
use App\Filament\Resources\Conservations\Tables\ConservationsTable;
use App\Models\Conservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ConservationResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Conservation::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Conservaciones';

    protected static ?string $modelLabel = 'Conservacion';

    protected static ?string $pluralModelLabel = 'Conservaciones';

    protected static ?string $recordTitleAttribute = 'id';

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'conservation.view';

    protected static ?string $viewAnyPermission = 'conservation.view';

    protected static ?string $createPermission = 'conservation.create';

    protected static ?string $updatePermission = 'conservation.update';

    protected static ?string $deletePermission = 'conservation.delete';

    public static function form(Schema $schema): Schema
    {
        return ConservationForm::form($schema);
    }

    public static function table(Table $table): Table
    {
        return ConservationsTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'client.name'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['client:id,name']);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Conservation $record */
        return $record->client?->name
            ? 'Conservacion - ' . $record->client->name
            : 'Conservacion #' . $record->getKey();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConservations::route('/'),
            'create' => CreateConservation::route('/create'),
            'edit' => EditConservation::route('/{record}/edit'),
        ];
    }
}
