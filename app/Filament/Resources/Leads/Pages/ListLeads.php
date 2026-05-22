<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Pages\PipelineLeads;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pipeline')
                ->label('Abrir pipeline')
                ->icon('heroicon-o-view-columns')
                ->url(PipelineLeads::getUrl()),
            CreateAction::make(),
        ];
    }
}
