<?php

namespace App\Filament\Resources\TechnicalBoard\Pages;

use App\Filament\Resources\TechnicalBoard\TechnicalBoardResource;
use Filament\Resources\Pages\ListRecords;

class ListTechnicalBoards extends ListRecords
{
    protected static string $resource = TechnicalBoardResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
