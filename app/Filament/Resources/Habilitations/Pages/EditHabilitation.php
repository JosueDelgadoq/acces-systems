<?php

namespace App\Filament\Resources\Habilitations\Pages;

use App\Filament\Resources\Habilitations\HabilitationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHabilitation extends EditRecord
{
    protected static string $resource = HabilitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

