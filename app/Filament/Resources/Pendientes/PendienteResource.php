<?php

namespace App\Filament\Resources\Pendientes;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Pendientes\Pages;
use App\Filament\Resources\Pendientes\Schemas\PendienteForm;
use App\Filament\Resources\Pendientes\Tables\PendienteTable;
use App\Models\Pendiente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PendienteResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Pendiente::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Pendientes';

    protected static ?string $modelLabel = 'Pendiente';

    protected static ?string $pluralModelLabel = 'Pendientes';

    protected static ?int $navigationSort = 2;

    protected static string | UnitEnum | null $navigationGroup = 'Gestion';

    protected static ?string $navigationPermission = 'pendiente.view';

    protected static ?string $viewAnyPermission = 'pendiente.view';

    protected static ?string $createPermission = 'pendiente.create';

    protected static ?string $updatePermission = 'pendiente.update';

    protected static ?string $deletePermission = 'pendiente.delete';

    public static function getNavigationBadge(): ?string
    {
        return (string) Pendiente::query()
            ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $vencidos = Pendiente::query()
            ->where('status', '!=', Pendiente::STATUS_COMPLETED)
            ->where('due_date', '<', now())
            ->count();

        return $vencidos > 0 ? 'danger' : 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forFilamentListing();
    }

    public static function form(Schema $schema): Schema
    {
        return PendienteForm::configure(
            $schema->columns([
                'xl' => 1,
            ]),
        );
    }

    public static function table(Table $table): Table
    {
        return PendienteTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendientes::route('/'),
            'create' => Pages\CreatePendiente::route('/create'),
            'edit' => Pages\EditPendiente::route('/{record}/edit'),
            'technical_board' => Pages\TechnicalBoardPendiente::route('/{record}/technical-board'),
        ];
    }
}
