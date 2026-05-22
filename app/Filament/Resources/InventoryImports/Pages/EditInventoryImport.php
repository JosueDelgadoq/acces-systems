<?php

namespace App\Filament\Resources\InventoryImports\Pages;

use App\Filament\Resources\InventoryImports\InventoryImportResource;
use App\Services\InventoryImportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInventoryImport extends EditRecord
{
    protected static string $resource = InventoryImportResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['file_name'] = basename((string) $data['file_path']);
        $data['file_disk'] = $data['file_disk'] ?? 'local';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->action(function (): void {
                    app(InventoryImportService::class)->preview($this->record, auth()->user());
                    $this->record->refresh();

                    Notification::make()
                        ->title('Preview generada')
                        ->success()
                        ->send();
                }),
            Action::make('import')
                ->label('Importar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): void {
                    app(InventoryImportService::class)->import($this->record, auth()->user());
                    $this->record->refresh();

                    Notification::make()
                        ->title('Importación completada')
                        ->success()
                        ->send();
                }),
            Action::make('rollback')
                ->label('Rollback')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn (): bool => filled($this->record->imported_at))
                ->action(function (): void {
                    app(InventoryImportService::class)->rollback($this->record, auth()->user());
                    $this->record->refresh();

                    Notification::make()
                        ->title('Rollback aplicado')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
