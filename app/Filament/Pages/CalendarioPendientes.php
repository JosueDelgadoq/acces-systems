<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use App\Models\Pendiente;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class CalendarioPendientes extends Page
{
    use HasPermissionControlledPage;

    protected static ?string $permission = 'pendiente.view';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendario pendientes';

    protected static string | UnitEnum | null $navigationGroup = 'BLOQUE B - Operaciones';

    protected static ?string $slug = 'calendario-pendientes';

    protected ?string $heading = 'Calendario operativo';

    protected string $view = 'filament.pages.calendario-pendientes';

    public function getTechnicians(): Collection
    {
        return User::query()
            ->role('tecnico')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getSummary(): array
    {
        $baseQuery = Pendiente::query()
            ->whereNotIn('status', [Pendiente::STATUS_COMPLETED, Pendiente::STATUS_CANCELLED]);

        return [
            'scheduled' => (clone $baseQuery)->whereNotNull('due_date')->count(),
            'today' => (clone $baseQuery)->whereDate('due_date', today())->count(),
            'overdue' => (clone $baseQuery)->whereDate('due_date', '<', today())->count(),
            'unassigned' => (clone $baseQuery)->whereNull('user_id')->count(),
        ];
    }

    public function getCalendarFeedUrl(): string
    {
        return url('/api/pendientes-calendar');
    }

    public function getCalendarUpdateUrl(): string
    {
        return url('/api/pendientes-update-date');
    }
}
