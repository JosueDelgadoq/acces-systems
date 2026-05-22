<?php

namespace App\Filament\Resources\SamuEvents\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Habilitations\HabilitationResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Pendientes\PendienteResource;
use App\Filament\Resources\SamuEvents\SamuEventResource;
use App\Models\SamuEvent;
use App\Services\Samu\SamuReviewService;
use App\Support\Modules\LeadPermissions;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSamuEvent extends EditRecord
{
    protected static string $resource = SamuEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrir_lead')
                ->label('Abrir lead')
                ->icon('heroicon-o-user')
                ->color('primary')
                ->visible(fn (): bool => filled($this->getRecord()->lead_id))
                ->url(fn (): ?string => $this->getRecord()->lead_id ? LeadResource::getUrl('edit', ['record' => $this->getRecord()->lead_id]) : null),
            Action::make('abrir_cliente')
                ->label('Abrir cliente')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->visible(fn (): bool => filled($this->getRecord()->client_id))
                ->url(fn (): ?string => $this->getRecord()->client_id ? ClientResource::getUrl('edit', ['record' => $this->getRecord()->client_id]) : null),
            Action::make('abrir_claim')
                ->label('Abrir claim')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->visible(fn (): bool => filled($this->getRecord()->claim_id))
                ->url(fn (): ?string => $this->getRecord()->claim_id ? ClaimResource::getUrl('edit', ['record' => $this->getRecord()->claim_id]) : null),
            Action::make('abrir_pendiente')
                ->label('Abrir pendiente')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->visible(fn (): bool => filled($this->getRecord()->pendiente_id))
                ->url(fn (): ?string => $this->getRecord()->pendiente_id ? PendienteResource::getUrl('edit', ['record' => $this->getRecord()->pendiente_id]) : null),
            Action::make('abrir_habilitacion')
                ->label('Abrir habilitacion')
                ->icon('heroicon-o-document-check')
                ->color('info')
                ->visible(fn (): bool => filled($this->getRecord()->habilitation_id))
                ->url(fn (): ?string => $this->getRecord()->habilitation_id ? HabilitationResource::getUrl('edit', ['record' => $this->getRecord()->habilitation_id]) : null),
            Action::make('agregar_nota')
                ->label('Agregar nota')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                ->form([
                    Textarea::make('note')
                        ->label('Nota interna')
                        ->rows(5)
                        ->required()
                        ->placeholder('Ej: revisar con Tahis antes de recatalogar / cliente aun sin respuesta / esperar documentacion'),
                ])
                ->action(function (array $data): void {
                    app(SamuReviewService::class)->addInternalNote(
                        $this->getRecord(),
                        $data['note'],
                        auth()->user(),
                    );

                    Notification::make()
                        ->title('Nota agregada')
                        ->body('La nota interna quedo auditada en el evento Samu.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'manual_reviewed_at',
                    ]);
                }),
            Action::make('recatalogar')
                ->label('Recatalogar')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                ->form([
                    Select::make('manual_classification')
                        ->label('Nuevo circuito')
                        ->options(SamuEvent::CLASSIFICATION_OPTIONS)
                        ->required()
                        ->default(fn (): ?string => $this->getRecord()->manual_classification ?: $this->getRecord()->classification),
                    Textarea::make('manual_review_notes')
                        ->label('Motivo o contexto')
                        ->rows(4)
                        ->placeholder('Ej: no es lead, corresponde a habilitacion administrativa'),
                ])
                ->action(function (array $data): void {
                    /** @var SamuEvent $record */
                    $record = $this->getRecord();

                    app(SamuReviewService::class)->recatalogar(
                        $record,
                        $data['manual_classification'],
                        $data['manual_review_notes'] ?? null,
                        auth()->user(),
                    );

                    Notification::make()
                        ->title('Evento recatalogado')
                        ->body('Se reenviara a la cola con el override manual aplicado.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'manual_classification',
                        'manual_review_notes',
                        'manual_reviewed_at',
                    ]);
                }),
            Action::make('descartar')
                ->label('Descartar')
                ->icon('heroicon-o-no-symbol')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                ->form([
                    Textarea::make('manual_review_notes')
                        ->label('Motivo del descarte')
                        ->rows(4)
                        ->required()
                        ->placeholder('Ej: llamada sin informacion util, prueba interna o no corresponde al CRM'),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    /** @var SamuEvent $record */
                    $record = $this->getRecord();

                    app(SamuReviewService::class)->discard(
                        $record,
                        trim((string) $data['manual_review_notes']),
                        auth()->user(),
                    );

                    Notification::make()
                        ->title('Evento descartado')
                        ->body('Queda auditado y no volvera a entrar por circuito automatico salvo que limpies el override.')
                        ->success()
                        ->send();
                }),
            Action::make('desvincular_relaciones')
                ->label('Desvincular')
                ->icon('heroicon-o-link-slash')
                ->color('danger')
                ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                ->form([
                    CheckboxList::make('unlink_fields')
                        ->label('Relaciones a desvincular')
                        ->options(SamuEvent::getUnlinkableRelationOptions())
                        ->columns(2)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    /** @var SamuEvent $record */
                    $record = $this->getRecord();

                    $unlinkedFields = app(SamuReviewService::class)->unlinkRelations(
                        $record,
                        $data['unlink_fields'] ?? [],
                    );

                    if ($unlinkedFields === []) {
                        Notification::make()
                            ->title('No se seleccionaron relaciones')
                            ->warning()
                            ->send();

                        return;
                    }

                    $record->forceFill($updates)->save();

                    Notification::make()
                        ->title('Relaciones desvinculadas')
                        ->body('Solo se desvinculo el evento. No se borraron registros relacionados.')
                        ->success()
                        ->send();

                    $this->refreshFormData($unlinkedFields);
                }),
            Action::make('volver_automatico')
                ->label('Volver a automatico')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => filled($this->getRecord()->manual_classification) && (auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false))
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var SamuEvent $record */
                    $record = $this->getRecord();

                    app(SamuReviewService::class)->clearManualOverride(
                        $record,
                        auth()->user(),
                    );

                    Notification::make()
                        ->title('Override manual limpiado')
                        ->body('El evento vuelve a procesarse con la logica automatica.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'manual_classification',
                        'manual_review_notes',
                        'manual_reviewed_at',
                    ]);
                }),
            Action::make('reprocesar')
                ->label('Reprocesar')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var SamuEvent $record */
                    $record = $this->getRecord();

                    app(SamuReviewService::class)->reprocess($record);

                    Notification::make()
                        ->title('Evento reenviado a la cola')
                        ->success()
                        ->send();
                }),
            Action::make('marcar_procesado')
                ->label('Marcar procesado')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => ! $this->getRecord()->processed && (auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false))
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var SamuEvent $record */
                    $record = $this->getRecord();

                    app(SamuReviewService::class)->markProcessed($record);

                    Notification::make()
                        ->title('Evento marcado como procesado')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'processed',
                        'processed_at',
                        'error_message',
                    ]);
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
