<?php

namespace App\Filament\Resources\GuiaItems\Pages;

use App\Filament\Resources\GuiaItems\GuiaItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGuiaItem extends EditRecord
{
    protected static string $resource = GuiaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
