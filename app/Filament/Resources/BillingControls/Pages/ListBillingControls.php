<?php

namespace App\Filament\Resources\BillingControls\Pages;

use App\Filament\Resources\BillingControls\BillingControlResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingControls extends ListRecords
{
    protected static string $resource = BillingControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

