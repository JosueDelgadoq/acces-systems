<?php

namespace App\Filament\Resources\TechnicalBoard\Pages;

use App\Filament\Resources\Pendientes\PendienteResource;
use App\Filament\Resources\TechnicalBoard\TechnicalBoardResource;
use App\Models\Pendiente;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditTechnicalBoard extends EditRecord
{
    protected static string $resource = TechnicalBoardResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editar_pendiente')
                ->label('Editar pendiente base')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => PendienteResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return TechnicalBoardResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function resolveRecord(int | string $key): Model
    {
        /** @var Pendiente $record */
        $record = parent::resolveRecord($key);

        return $record->load(Pendiente::query()->forHistoryPanel()->getEagerLoads());
    }
}
