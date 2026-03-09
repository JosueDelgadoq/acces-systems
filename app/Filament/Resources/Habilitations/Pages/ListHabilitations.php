<?php

namespace App\Filament\Resources\Habilitations\Pages;

use App\Filament\Resources\Habilitations\HabilitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHabilitations extends ListRecords
{
    protected static string $resource = HabilitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

