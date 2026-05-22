<?php

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendienteResource;
use App\Models\Pendiente;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditPendiente extends EditRecord
{
    protected static string $resource = PendienteResource::class;

    protected Width | string | null $maxContentWidth = Width::ScreenTwoExtraLarge;

    protected function getHeaderActions(): array
    {
        $actions = [
            DeleteAction::make(),
        ];

        if (\App\Models\Pendiente::supportsTechnicalBoard()) {
            array_unshift($actions, Action::make('technical_board')
                ->label('Tablero tecnico')
                ->icon('heroicon-o-rectangle-group')
                ->color('primary')
                ->url(fn (): string => PendienteResource::getUrl('technical_board', ['record' => $this->getRecord()])));
        }

        return $actions;
    }

    protected function resolveRecord(int | string $key): Model
    {
        /** @var Pendiente $record */
        $record = parent::resolveRecord($key);

        return $record->load(Pendiente::query()->forHistoryPanel()->getEagerLoads());
    }
}
