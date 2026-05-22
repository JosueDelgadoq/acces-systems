<?php

namespace App\Filament\Resources\SamuEvents;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\SamuEvents\Pages\EditSamuEvent;
use App\Filament\Resources\SamuEvents\Pages\ListSamuEvents;
use App\Filament\Resources\SamuEvents\Schemas\SamuEventForm;
use App\Filament\Resources\SamuEvents\Tables\SamuEventsTable;
use App\Models\SamuEvent;
use App\Support\Modules\LeadPermissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SamuEventResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = SamuEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationLabel = 'Eventos Samu';

    protected static ?string $modelLabel = 'Evento Samu';

    protected static ?string $pluralModelLabel = 'Eventos Samu';

    protected static ?string $navigationPermission = LeadPermissions::VIEW;

    protected static ?string $viewAnyPermission = LeadPermissions::VIEW;

    protected static ?string $updatePermission = LeadPermissions::VIEW;

    public static function getNavigationBadge(): ?string
    {
        $pending = SamuEvent::query()->needsReview()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        if (SamuEvent::query()->withErrors()->exists()) {
            return 'danger';
        }

        return SamuEvent::query()->needsReview()->exists()
            ? 'warning'
            : 'primary';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'lead:id,crm_id,nombre,apellido',
                'client:id,name',
                'claim:id,title',
                'pendiente:id,type',
                'habilitation:id,equipment,status',
                'manualReviewer:id,name',
                'latestNote.user:id,name',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return SamuEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SamuEventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSamuEvents::route('/'),
            'edit' => EditSamuEvent::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
