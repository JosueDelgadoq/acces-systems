<?php

namespace App\Filament\Resources\SamuEvents\Tables;

use App\Filament\Resources\SamuEvents\SamuEventResource;
use App\Models\SamuEvent;
use App\Services\Samu\SamuReviewService;
use App\Support\Modules\LeadPermissions;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SamuEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('external_id')
                    ->label('External ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('event_type')
                    ->label('Evento')
                    ->badge()
                    ->searchable(),
                TextColumn::make('summary')
                    ->label('Resumen')
                    ->limit(70)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('interest_level')
                    ->label('Interes')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => SamuEvent::getInterestLevelLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'alto' => 'success',
                        'medio' => 'warning',
                        'bajo' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('pipeline_stage')
                    ->label('Pipeline')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('classification')
                    ->label('Catalogacion')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => SamuEvent::getClassificationLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
                        SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP => 'primary',
                        SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP => 'info',
                        SamuEvent::CLASSIFICATION_MIXED => 'warning',
                        SamuEvent::CLASSIFICATION_POST_SALE_CLAIM => 'danger',
                        SamuEvent::CLASSIFICATION_POST_SALE_SUPPORT,
                        SamuEvent::CLASSIFICATION_POST_SALE_MAINTENANCE,
                        SamuEvent::CLASSIFICATION_POST_SALE_DIAGNOSIS,
                        SamuEvent::CLASSIFICATION_POST_SALE_INSTALLATION,
                        SamuEvent::CLASSIFICATION_POST_SALE_DISPATCH => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('review_status')
                    ->label('Revision')
                    ->badge()
                    ->getStateUsing(fn (SamuEvent $record): string => $record->getReviewStatusMeta()['label'])
                    ->color(fn (SamuEvent $record): string => $record->getReviewStatusMeta()['color']),
                TextColumn::make('manual_classification')
                    ->label('Override')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? SamuEvent::getClassificationLabel($state) : 'Auto')
                    ->color(fn (?string $state): string => filled($state) ? 'warning' : 'gray')
                    ->toggleable(),
                IconColumn::make('processed')
                    ->label('Procesado')
                    ->boolean(),
                TextColumn::make('lead.crm_id')
                    ->label('Lead')
                    ->placeholder('Sin lead')
                    ->toggleable(),
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->placeholder('Sin cliente')
                    ->toggleable(),
                TextColumn::make('claim.title')
                    ->label('Claim')
                    ->placeholder('Sin claim')
                    ->limit(32)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pendiente.type')
                    ->label('Pendiente')
                    ->placeholder('Sin pendiente')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('habilitation.equipment')
                    ->label('Habilitacion')
                    ->placeholder('Sin habilitacion')
                    ->limit(32)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manualReviewer.name')
                    ->label('Reviso')
                    ->placeholder('Automatico')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes_count')
                    ->label('Notas')
                    ->counts('notes')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('latestNote.note')
                    ->label('Ultima nota')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('manual_reviewed_at')
                    ->label('Rev. manual')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->wrap()
                    ->color('danger')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('processed')
                    ->label('Procesado'),
                SelectFilter::make('interest_level')
                    ->label('Interes')
                    ->options(SamuEvent::INTEREST_LEVEL_OPTIONS),
                SelectFilter::make('event_type')
                    ->label('Tipo de evento')
                    ->options(fn (): array => SamuEvent::query()
                        ->whereNotNull('event_type')
                        ->orderBy('event_type')
                        ->pluck('event_type', 'event_type')
                        ->all()),
                SelectFilter::make('classification')
                    ->label('Catalogacion')
                    ->options(SamuEvent::CLASSIFICATION_OPTIONS),
                TernaryFilter::make('manual_override')
                    ->label('Override manual')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('manual_classification'),
                        false: fn ($query) => $query->whereNull('manual_classification'),
                        blank: fn ($query) => $query,
                    ),
                TernaryFilter::make('has_error')
                    ->label('Con error')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('error_message'),
                        false: fn ($query) => $query->whereNull('error_message'),
                        blank: fn ($query) => $query,
                    ),
                TernaryFilter::make('low_signal')
                    ->label('Baja senal')
                    ->queries(
                        true: fn ($query) => $query->lowSignal(),
                        false: fn ($query) => $query->withoutLowSignal(),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->actions([
                Action::make('ver_detalle')
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (SamuEvent $record): string => SamuEventResource::getUrl('edit', ['record' => $record])),
                Action::make('reprocesar')
                    ->label('Reprocesar evento')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                    ->action(function (SamuEvent $record): void {
                        app(SamuReviewService::class)->reprocess($record);

                        Notification::make()
                            ->title('Evento reenviado a la cola')
                            ->body('Se volvera a procesar el webhook de Samu.ai.')
                            ->success()
                            ->send();
                    }),
                Action::make('marcar_procesado')
                    ->label('Marcar como procesado')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SamuEvent $record): bool => ! $record->processed && (auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false))
                    ->action(function (SamuEvent $record): void {
                        app(SamuReviewService::class)->markProcessed($record);

                        Notification::make()
                            ->title('Evento marcado como procesado')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('reprocesar_masivo')
                        ->label('Reprocesar')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->action(function (EloquentCollection $records): void {
                            $service = app(SamuReviewService::class);

                            foreach ($records as $record) {
                                $service->reprocess($record);
                            }

                            Notification::make()
                                ->title('Eventos reenviados a la cola')
                                ->body($records->count() . ' evento(s) se volveran a procesar.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('marcar_procesado_masivo')
                        ->label('Marcar procesados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->action(function (EloquentCollection $records): void {
                            $service = app(SamuReviewService::class);

                            foreach ($records as $record) {
                                $service->markProcessed($record);
                            }

                            Notification::make()
                                ->title('Eventos marcados como procesados')
                                ->body($records->count() . ' evento(s) quedaron resueltos manualmente.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('recatalogar_masivo')
                        ->label('Recatalogar')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->form([
                            Select::make('manual_classification')
                                ->label('Nuevo circuito')
                                ->options(SamuEvent::CLASSIFICATION_OPTIONS)
                                ->required(),
                            Textarea::make('manual_review_notes')
                                ->label('Motivo o contexto')
                                ->rows(4)
                                ->placeholder('Ej: lote administrativo, corresponde a habilitaciones'),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $service = app(SamuReviewService::class);

                            foreach ($records as $record) {
                                $service->recatalogar(
                                    $record,
                                    $data['manual_classification'],
                                    $data['manual_review_notes'] ?? null,
                                    auth()->user(),
                                );
                            }

                            Notification::make()
                                ->title('Eventos recatalogados')
                                ->body($records->count() . ' evento(s) se reenviaron con override manual.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('descartar_masivo')
                        ->label('Descartar')
                        ->icon('heroicon-o-no-symbol')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->form([
                            Textarea::make('manual_review_notes')
                                ->label('Motivo del descarte')
                                ->rows(4)
                                ->required()
                                ->placeholder('Ej: pruebas internas, llamada sin datos, no corresponde al CRM'),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $service = app(SamuReviewService::class);
                            $notes = trim((string) $data['manual_review_notes']);

                            foreach ($records as $record) {
                                $service->discard($record, $notes, auth()->user());
                            }

                            Notification::make()
                                ->title('Eventos descartados')
                                ->body($records->count() . ' evento(s) quedaron fuera de circuito con auditoria.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('volver_automatico_masivo')
                        ->label('Volver a automatico')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->action(function (EloquentCollection $records): void {
                            $service = app(SamuReviewService::class);

                            foreach ($records as $record) {
                                $service->clearManualOverride($record, auth()->user());
                            }

                            Notification::make()
                                ->title('Overrides limpiados')
                                ->body($records->count() . ' evento(s) volvieron a la logica automatica.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('desvincular_relaciones_masivo')
                        ->label('Desvincular')
                        ->icon('heroicon-o-link-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->form([
                            CheckboxList::make('unlink_fields')
                                ->label('Relaciones a desvincular')
                                ->options(SamuEvent::getUnlinkableRelationOptions())
                                ->columns(2)
                                ->required(),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $service = app(SamuReviewService::class);
                            $fields = $data['unlink_fields'] ?? [];
                            $affectedCount = 0;

                            foreach ($records as $record) {
                                $unlinkedFields = $service->unlinkRelations($record, $fields);

                                if ($unlinkedFields !== []) {
                                    $affectedCount++;
                                }
                            }

                            if ($affectedCount === 0) {
                                Notification::make()
                                    ->title('No se seleccionaron relaciones')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Relaciones desvinculadas')
                                ->body($affectedCount . ' evento(s) fueron desvinculados sin borrar registros relacionados.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('agregar_nota_masivo')
                        ->label('Agregar nota')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()?->canAccess(LeadPermissions::ASSIGN) ?? false)
                        ->form([
                            Textarea::make('note')
                                ->label('Nota interna')
                                ->rows(5)
                                ->required()
                                ->placeholder('Ej: lote revisado, esperar respuesta del cliente, consultar con Tahis'),
                        ])
                        ->action(function (EloquentCollection $records, array $data): void {
                            $service = app(SamuReviewService::class);
                            $note = trim((string) $data['note']);

                            foreach ($records as $record) {
                                $service->addInternalNote($record, $note, auth()->user());
                            }

                            Notification::make()
                                ->title('Notas agregadas')
                                ->body($records->count() . ' evento(s) recibieron una nota interna auditada.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
