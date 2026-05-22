<?php

namespace App\Filament\Resources\TechnicalBoard;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Pendientes\Schemas\PendienteTechnicalBoardForm;
use App\Filament\Resources\TechnicalBoard\Pages\EditTechnicalBoard;
use App\Filament\Resources\TechnicalBoard\Pages\ListTechnicalBoards;
use App\Filament\Resources\TechnicalBoard\Tables\TechnicalBoardTable;
use App\Models\Pendiente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TechnicalBoardResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Pendiente::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Tablero tecnico';

    protected static ?string $modelLabel = 'Tarea tecnica';

    protected static ?string $pluralModelLabel = 'Tablero tecnico';

    protected static ?int $navigationSort = 3;

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'technical_board.view';

    protected static ?string $viewAnyPermission = 'technical_board.view';

    protected static ?string $updatePermission = 'technical_board.update';

    public static function shouldRegisterNavigation(): bool
    {
        return Pendiente::supportsTechnicalBoard() && static::hasPermission(static::$navigationPermission);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Pendiente::supportsTechnicalBoard()) {
            return null;
        }

        return (string) Pendiente::query()
            ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forFilamentListing();
    }

    public static function form(Schema $schema): Schema
    {
        return PendienteTechnicalBoardForm::configure(
            $schema->columns([
                'xl' => 1,
            ]),
        );
    }

    public static function table(Table $table): Table
    {
        return TechnicalBoardTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTechnicalBoards::route('/'),
            'edit' => EditTechnicalBoard::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return static::hasPermission(static::$updatePermission);
    }
}
