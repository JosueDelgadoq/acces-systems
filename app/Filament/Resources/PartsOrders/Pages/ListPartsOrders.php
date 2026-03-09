<?php

namespace App\Filament\Resources\PartsOrders\Pages;

use App\Filament\Resources\PartsOrders\PartsOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPartsOrders extends ListRecords
{
    protected static string $resource = PartsOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

