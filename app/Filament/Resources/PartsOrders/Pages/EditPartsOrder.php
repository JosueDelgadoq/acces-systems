<?php

namespace App\Filament\Resources\PartsOrders\Pages;

use App\Filament\Resources\PartsOrders\PartsOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPartsOrder extends EditRecord
{
    protected static string $resource = PartsOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

