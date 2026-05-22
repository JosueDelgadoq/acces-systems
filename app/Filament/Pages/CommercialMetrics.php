<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use App\Models\Lead;
use App\Services\CommercialMetricsService;
use App\Support\Modules\LeadPermissions;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use UnitEnum;

class CommercialMetrics extends Page
{
    use HasPermissionControlledPage;

    protected static ?string $permission = LeadPermissions::VIEW;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationLabel = 'Metricas';

    protected static ?string $title = 'Metricas comerciales';

    protected static ?string $slug = 'metricas-comerciales';

    protected ?string $heading = 'Metricas comerciales';

    protected string $view = 'filament.pages.commercial-metrics';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $periodPreset = 'month';

    public ?string $fromDate = null;

    public ?string $untilDate = null;

    public ?string $commercialId = null;

    protected ?array $reportData = null;

    public function mount(): void
    {
        $requestedPreset = (string) request()->query('period', $this->periodPreset);

        if (array_key_exists($requestedPreset, $this->getPresetOptions())) {
            $this->periodPreset = $requestedPreset;
        }

        $this->syncDatesWithPreset($this->periodPreset);

        if (filled(request()->query('from'))) {
            $this->fromDate = Carbon::parse((string) request()->query('from'))->toDateString();
            $this->periodPreset = 'custom';
        }

        if (filled(request()->query('until'))) {
            $this->untilDate = Carbon::parse((string) request()->query('until'))->toDateString();
            $this->periodPreset = 'custom';
        }

        if (filled(request()->query('commercial_id'))) {
            $this->commercialId = (string) request()->integer('commercial_id');
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['fromDate', 'untilDate'], true)) {
            $this->periodPreset = 'custom';
        }

        $this->reportData = null;
    }

    public function setPeriodPreset(string $preset): void
    {
        if (! array_key_exists($preset, $this->getPresetOptions())) {
            return;
        }

        $this->periodPreset = $preset;
        $this->syncDatesWithPreset($preset);
        $this->reportData = null;
    }

    public function resetFilters(): void
    {
        $this->commercialId = null;
        $this->setPeriodPreset('month');
    }

    public function getPresetOptions(): array
    {
        return [
            'week' => '7 dias',
            '30d' => '30 dias',
            'month' => 'Mes actual',
            'quarter' => 'Trimestre',
            'year' => 'Ano',
            'custom' => 'Personalizado',
        ];
    }

    public function getCommercialOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        return Lead::query()
            ->visibleTo($user)
            ->whereNotNull('comercial_asignado_id')
            ->join('users', 'users.id', '=', 'leads.comercial_asignado_id')
            ->orderBy('users.name')
            ->distinct()
            ->pluck('users.name', 'users.id')
            ->all();
    }

    public function getReportData(): array
    {
        if ($this->reportData !== null) {
            return $this->reportData;
        }

        [$from, $until] = $this->resolveDateRange();

        return $this->reportData = app(CommercialMetricsService::class)->build(
            viewer: auth()->user(),
            from: $from,
            until: $until,
            commercialId: $this->selectedCommercialId(),
        );
    }

    public function getExportUrl(string $format): string
    {
        $routeName = match ($format) {
            'pdf' => 'commercial-metrics.pdf',
            default => 'commercial-metrics.xlsx',
        };

        return route($routeName, array_filter([
            'from' => $this->fromDate,
            'until' => $this->untilDate,
            'commercial_id' => $this->commercialId,
        ], fn ($value) => filled($value)));
    }

    protected function resolveDateRange(): array
    {
        $from = filled($this->fromDate)
            ? Carbon::parse($this->fromDate)
            : now()->startOfMonth();

        $until = filled($this->untilDate)
            ? Carbon::parse($this->untilDate)
            : now()->endOfMonth();

        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy(), $from->copy()];
        }

        return [$from, $until];
    }

    protected function syncDatesWithPreset(string $preset): void
    {
        $today = now();

        [$from, $until] = match ($preset) {
            'week' => [$today->copy()->subDays(6), $today->copy()],
            '30d' => [$today->copy()->subDays(29), $today->copy()],
            'quarter' => [$today->copy()->startOfQuarter(), $today->copy()->endOfQuarter()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [$this->fromDate ? Carbon::parse($this->fromDate) : $today->copy()->startOfMonth(), $this->untilDate ? Carbon::parse($this->untilDate) : $today->copy()->endOfMonth()],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };

        $this->fromDate = $from->toDateString();
        $this->untilDate = $until->toDateString();
    }

    protected function selectedCommercialId(): ?int
    {
        return filled($this->commercialId)
            ? (int) $this->commercialId
            : null;
    }
}
