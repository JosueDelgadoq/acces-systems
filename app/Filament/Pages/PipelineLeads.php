<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use App\Models\Lead;
use App\Support\Modules\LeadPermissions;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class PipelineLeads extends Page
{
    use HasPermissionControlledPage;

    protected static ?string $permission = LeadPermissions::VIEW;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-view-columns';

    protected ?string $heading = 'Pipeline comercial';

    protected string $view = 'filament.pages.pipeline-leads';

    protected ?Collection $groupedLeads = null;

    public function getColumns(): array
    {
        return array_keys(Lead::getPipelineOptions());
    }

    public function getLeads(): Collection
    {
        return $this->groupedLeads ??= Lead::query()
            ->with([
                'comercialAsignado:id,name',
                'nextPendingSeguimiento' => fn ($query) => $query->select([
                    'seguimientos.id',
                    'seguimientos.lead_id',
                    'seguimientos.proxima_accion',
                    'seguimientos.fecha_proxima_accion',
                    'seguimientos.estado',
                ]),
            ])
            ->visibleTo(auth()->user())
            ->orderByRaw("CASE WHEN fecha_ultimo_seguimiento IS NULL THEN 0 ELSE 1 END")
            ->orderBy('fecha_ultimo_seguimiento')
            ->orderBy('fecha_ingreso', 'desc')
            ->get()
            ->groupBy('estado_pipeline');
    }

    public function getStageTotals(): array
    {
        $leads = $this->getLeads();

        return collect($this->getColumns())
            ->mapWithKeys(fn (string $stage): array => [
                $stage => $leads->get($stage, collect())->count(),
            ])
            ->all();
    }

    public function canChangeStatus(): bool
    {
        return auth()->user()?->can(LeadPermissions::CHANGE_STATUS) ?? false;
    }

    public function moveLead(int $leadId, string $newStatus): void
    {
        abort_unless($this->canChangeStatus(), 403);

        if (! array_key_exists($newStatus, Lead::getPipelineOptions())) {
            return;
        }

        $lead = Lead::query()
            ->visibleTo(auth()->user())
            ->find($leadId);

        if (! $lead || $lead->estado_pipeline === $newStatus) {
            return;
        }

        $lead->update([
            'estado_pipeline' => $newStatus,
        ]);

        $this->groupedLeads = null;
    }
}
