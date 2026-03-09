<?php

namespace App\Filament\Resources\Conservations\Pages;

use App\Filament\Resources\Conservations\ConservationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditConservation extends EditRecord
{
    protected static string $resource = ConservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
