<?php

namespace App\Filament\Resources\Conservations\Pages;

use App\Filament\Resources\Conservations\ConservationResource;
use App\Models\Conservation;
use App\Models\User;
use App\Services\ConservationContractService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditConservation extends EditRecord
{
    protected static string $resource = ConservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registrar_servicio')
                ->label('Registrar mantenimiento')
                ->color('success')
                ->visible(fn (Conservation $record): bool => ! $record->canBeRenewed())
                ->form([
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->default(now())
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->required(),

                    Select::make('technician_id')
                        ->label('Tecnico')
                        ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    TextInput::make('remito')
                        ->label('Numero de remito')
                        ->placeholder('Ej: 6845')
                        ->numeric()
                        ->rule('digits:4')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Observaciones'),
                ])
                ->action(function (array $data, Conservation $record): void {
                    app(ConservationContractService::class)->registerService($record, $data);
                    $this->record = $record->fresh();

                    $message = $this->record->isRenewalRequired()
                        ? 'Se registró la última conservación del contrato. Ahora requiere renovación.'
                        : 'Conservación registrada y próximo servicio recalculado.';

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'current_service_number',
                        'next_service_date',
                        'last_service_date',
                        'completed_at',
                        'renewal_required_at',
                        'contract_cycle_number',
                        'contract_status',
                    ]);
                }),

            Action::make('renovar_contrato')
                ->label('Renovar contrato')
                ->color('warning')
                ->visible(fn (Conservation $record): bool => $record->canBeRenewed())
                ->form([
                    DatePicker::make('renewed_at')
                        ->label('Fecha de renovacion')
                        ->default(now())
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->required(),

                    DatePicker::make('new_start_date')
                        ->label('Nuevo inicio de contrato')
                        ->default(fn (Conservation $record) => optional($record->expiration_date)->addDay() ?? now())
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->required(),

                    DatePicker::make('new_expiration_date')
                        ->label('Nuevo vencimiento')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->required(),

                    Select::make('frequency')
                        ->label('Frecuencia de mantenimiento')
                        ->options([
                            'mensual' => 'Mensual',
                            'trimestral' => 'Trimestral',
                            'semestral' => 'Semestral',
                            'anual' => 'Anual',
                        ])
                        ->default(fn (Conservation $record) => $record->frequency)
                        ->required(),

                    TextInput::make('total_services')
                        ->label('Cantidad de servicios del nuevo ciclo')
                        ->numeric()
                        ->minValue(1)
                        ->default(fn (Conservation $record) => $record->total_services)
                        ->required(),

                    Textarea::make('notes')
                        ->label('Detalle de la renovacion')
                        ->placeholder('Ej: Renovacion anual con 12 mantenimientos mensuales.'),
                ])
                ->action(function (array $data, Conservation $record): void {
                    app(ConservationContractService::class)->renewContract($record, $data, auth()->user());
                    $this->record = $record->fresh();

                    Notification::make()
                        ->title('Contrato renovado y ciclo reiniciado en 01.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'start_date',
                        'expiration_date',
                        'frequency',
                        'total_services',
                        'current_service_number',
                        'next_service_date',
                        'last_service_date',
                        'completed_at',
                        'renewal_required_at',
                        'contract_cycle_number',
                        'contract_status',
                    ]);
                }),

            DeleteAction::make(),
        ];
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title('No se pudo completar la acción')
            ->body(collect($exception->errors())->flatten()->first())
            ->danger()
            ->send();
    }
}
