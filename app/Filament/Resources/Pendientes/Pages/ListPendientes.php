<?php

namespace App\Filament\Resources\Pendientes\Pages;

use App\Models\Pendiente;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pendientes\PendienteResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListPendientes extends ListRecords
{
    protected static string $resource = PendienteResource::class;

    protected function getTableQuery(): Builder | Relation | null
    {
        return parent::getTableQuery()?->withoutCompleted();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
