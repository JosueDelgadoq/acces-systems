<?php

namespace App\Filament\Resources\SamuEvents\Pages;

use App\Filament\Resources\SamuEvents\SamuEventResource;
use App\Filament\Resources\SamuEvents\Widgets\SamuReviewStats;
use App\Models\SamuEvent;
use App\Services\Samu\SamuReviewExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSamuEvents extends ListRecords
{
    protected static string $resource = SamuEventResource::class;

    protected static ?string $title = 'Bandeja de revision Samu';

    protected ?string $heading = 'Bandeja de revision Samu';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportar_csv')
                ->label('Exportar CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => app(SamuReviewExportService::class)->streamCurrentViewCsv(
                    $this->getTableQueryForExport(),
                    is_string($this->activeTab) ? $this->activeTab : null,
                )),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SamuReviewStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 6,
        ];
    }

    public function getTabs(): array
    {
        return [
            'bandeja' => Tab::make('Bandeja')
                ->badge(fn (): int => SamuEvent::query()->needsReview()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->needsReview()),
            'baja_senal' => Tab::make('Baja senal')
                ->badge(fn (): int => SamuEvent::query()->lowSignal()->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->lowSignal()),
            'sin_catalogar' => Tab::make('Sin catalogar')
                ->badge(fn (): int => SamuEvent::query()->unclassifiedReview()->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->unclassifiedReview()),
            'override_manual' => Tab::make('Override manual')
                ->badge(fn (): int => SamuEvent::query()->withManualOverride()->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->withManualOverride()),
            'descartados' => Tab::make('Descartados')
                ->badge(fn (): int => SamuEvent::query()->discarded()->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->discarded()),
            'con_error' => Tab::make('Con error')
                ->badge(fn (): int => SamuEvent::query()->withErrors()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->withErrors()),
            'todos' => Tab::make('Todos')
                ->badge(fn (): int => SamuEvent::query()->count())
                ->badgeColor('success'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'bandeja';
    }
}
