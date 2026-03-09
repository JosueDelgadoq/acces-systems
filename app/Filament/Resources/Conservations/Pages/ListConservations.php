<?php

namespace App\Filament\Resources\Conservations\Pages;

use App\Filament\Resources\Conservations\ConservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConservations extends ListRecords
{
    protected static string $resource = ConservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
