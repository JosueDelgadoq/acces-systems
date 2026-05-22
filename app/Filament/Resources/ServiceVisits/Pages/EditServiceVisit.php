<?php

namespace App\Filament\Resources\ServiceVisits\Pages;

use App\Filament\Resources\Pendientes\PendienteResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use App\Filament\Resources\ServiceVisits\ServiceVisitResource;
use App\Models\Pendiente;

class EditServiceVisit extends EditRecord
{
    protected static string $resource = ServiceVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrir_pendiente')
                ->label('Abrir pendiente')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->visible(fn (): bool => filled($this->getRecord()->pendiente_id))
                ->url(function (): ?string {
                    $pendienteId = $this->getRecord()->pendiente_id;

                    if (! $pendienteId) {
                        return null;
                    }

                    $route = Pendiente::supportsTechnicalBoard() ? 'technical_board' : 'edit';

                    return PendienteResource::getUrl($route, ['record' => $pendienteId]);
                }),
        ];
    }
}
