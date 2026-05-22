<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Concerns\HasPermissionControlledResource;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\RelationManagers\SeguimientosRelationManager;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Lead;
use App\Support\Modules\LeadPermissions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LeadResource extends Resource
{
    use HasPermissionControlledResource;

    protected static ?string $model = Lead::class;

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationPermission = LeadPermissions::VIEW;

    protected static ?string $viewAnyPermission = LeadPermissions::VIEW;

    protected static ?string $createPermission = LeadPermissions::CREATE;

    protected static ?string $updatePermission = LeadPermissions::UPDATE;

    protected static ?string $deletePermission = LeadPermissions::DELETE;

    protected static ?string $navigationLabel = 'Leads';

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $pluralModelLabel = 'Leads';

    public static function getNavigationBadge(): ?string
    {
        return (string) Lead::query()
            ->openPipeline()
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $stalled = Lead::query()
            ->openPipeline()
            ->whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(5))
            ->count();

        return $stalled > 0 ? 'danger' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(LeadForm::make());
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->canAccess(LeadPermissions::VIEW)
            || auth()->user()?->canAccess(LeadPermissions::VIEW_ALL)
            || false;
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'comercialAsignado:id,name',
                'nextPendingSeguimiento' => fn ($query) => $query->select([
                    'seguimientos.id',
                    'seguimientos.lead_id',
                    'seguimientos.proxima_accion',
                    'seguimientos.fecha_proxima_accion',
                    'seguimientos.estado',
                ]),
                'histories.user:id,name',
            ])
            ->visibleTo(auth()->user());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SeguimientosRelationManager::class,
        ];
    }
}
