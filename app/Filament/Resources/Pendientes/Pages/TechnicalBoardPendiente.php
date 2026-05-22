<?php

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendienteResource;
use App\Filament\Resources\Pendientes\Schemas\PendienteTechnicalBoardForm;
use App\Models\Pendiente;
use App\Services\ProductoUnidadAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class TechnicalBoardPendiente extends EditRecord
{
    protected static string $resource = PendienteResource::class;

    protected static ?string $title = 'Tablero tecnico';

    protected static ?string $breadcrumb = 'Tablero tecnico';

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @var array<int, int>
     */
    protected array $inventoryUnitIds = [];

    public function form(Schema $schema): Schema
    {
        return PendienteTechnicalBoardForm::configure(
            $schema->columns([
                'xl' => 1,
            ]),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editar_base')
                ->label('Editar pendiente')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => PendienteResource::getUrl('edit', ['record' => $this->getRecord()])),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return PendienteResource::getUrl('technical_board', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['inventory_unit_ids'] = $this->getRecord()
            ->activeUnitAssignments()
            ->pluck('producto_unidad_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->inventoryUnitIds = collect($data['inventory_unit_ids'] ?? [])
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();

        unset($data['inventory_unit_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(ProductoUnidadAssignmentService::class)->syncForPendiente(
            $this->getRecord(),
            $this->inventoryUnitIds,
            auth()->user(),
            'Reserva actualizada desde tablero tecnico.',
        );
    }

    protected function resolveRecord(int | string $key): Model
    {
        /** @var Pendiente $record */
        $record = parent::resolveRecord($key);

        return $record->load(Pendiente::query()->forHistoryPanel()->getEagerLoads());
    }
}
