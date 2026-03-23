<?php

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendienteResource;
use Filament\Resources\Pages\ListRecords;

class ListPendientes extends ListRecords
{
    protected static string $resource = PendienteResource::class;
}