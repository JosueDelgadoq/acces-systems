<?php

namespace App\Filament\Resources\ServiceVisits\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ServiceVisits\ServiceVisitResource;
use App\Filament\Resources\ServiceVisits\Tables\ServiceVisitTable;
use Filament\Tables\Table;

class ListServiceVisits extends ListRecords
{
    protected static string $resource = ServiceVisitResource::class;

    public function table(Table $table): Table
    {
        return ServiceVisitTable::configure($table);
    }
}