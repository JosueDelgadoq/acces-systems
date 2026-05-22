<?php

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendienteResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreatePendiente extends CreateRecord
{
    protected static string $resource = PendienteResource::class;

    protected Width | string | null $maxContentWidth = Width::ScreenTwoExtraLarge;
}
