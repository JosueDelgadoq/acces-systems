<?php

namespace App\Filament\Resources\BillingControls\Pages;

use App\Filament\Resources\BillingControls\BillingControlResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingControl extends EditRecord
{
    protected static string $resource = BillingControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

