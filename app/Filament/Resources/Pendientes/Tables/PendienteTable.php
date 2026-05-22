<?php

namespace App\Filament\Resources\Pendientes\Tables;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\ServiceVisits\ServiceVisitResource;
use App\Models\Pendiente;
use App\Services\ProductoUnidadAssignmentService;
use App\Services\PendienteReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendienteTable
{
    public static function configure(Table $table): Table
    {
        $columns = [];
        $groups = [self::statusGroup(), Group::make('client.name')->label('Cliente')];
        $filters = [
            SelectFilter::make('status')
                ->label('Estado')
                ->options(collect(Pendiente::getStatusOptions())
                    ->except(Pendiente::STATUS_COMPLETED)
                    ->all()),
            SelectFilter::make('priority')
                ->label('Prioridad')
                ->options(Pendiente::getPriorityOptions()),
            SelectFilter::make('type')
                ->label('Tipo de tarea')
                ->options(Pendiente::getTypeOptions()),
            Filter::make('fecha')
                ->label('Rango de fechas')
                ->form([
                    DatePicker::make('from')->label('Desde'),
                    DatePicker::make('until')->label('Hasta'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('due_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('due_date', '<=', $date));
                }),
            Filter::make('vencidos')
                ->label('Vencidos')
                ->query(fn (Builder $query): Builder => $query
                    ->where('status', '!=', Pendiente::STATUS_COMPLETED)
                    ->where('due_date', '<', now())),
            Filter::make('sin_asignar')
                ->label('Sin tecnico')
                ->query(fn (Builder $query): Builder => $query->whereNull('user_id')),
            SelectFilter::make('mine')
                ->label('Mis pendientes')
                ->options(['1' => 'Solo mios'])
                ->query(function (Builder $query, array $data): void {
                    if ($data['value'] ?? false) {
                        $query->where('user_id', auth()->id());
                    }
                }),
        ];

         $columns[] = TextColumn::make('type')
            ->label('Tipo de tarea')
            ->badge()
            ->sortable()
            ->formatStateUsing(fn (?string $state): string => Pendiente::getTypeOptions()[$state] ?? (string) $state);

        $columns[] = TextColumn::make('client.name')
            ->label('Cliente')
            ->sortable()
            ->searchable()
            ->url(fn (Pendiente $record): string => ClientResource::getUrl('edit', ['record' => $record->client_id]));

        $columns[] = TextColumn::make('priority')
            ->label('Prioridad final')
            ->badge()
            ->sortable()
            ->formatStateUsing(fn (?string $state): string => Pendiente::getPriorityOptions()[$state] ?? 'Sin definir')
            ->color(fn (?string $state): string => match ($state) {
                'alta' => 'danger',
                'media' => 'warning',
                'baja' => 'success',
                default => 'gray',
            });

        $columns[] = TextColumn::make('user.name')
            ->label('Tecnico')
            ->placeholder('Sin asignar');

        $columns[] = TextColumn::make('created_at')
            ->label('Creado')
            ->dateTime('d/m/Y H:i')
            ->toggleable();

        $columns[] = TextColumn::make('due_date')
            ->label('Fecha programada (sugerida)')
            ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : 'A programar');

        $columns[] = TextColumn::make('status')
            ->label('Estado')
            ->badge()
            ->sortable()
            ->formatStateUsing(function (?string $state, Pendiente $record): string {
                if (
                    $record->status !== Pendiente::STATUS_COMPLETED
                    && $record->due_date
                    && Carbon::parse($record->due_date)->isBefore(Carbon::today())
                ) {
                    return 'Vencido';
                }

                return Pendiente::getStatusLabel($state);
            })
            ->color(function (?string $state, Pendiente $record): string {
                if (
                    $record->status !== Pendiente::STATUS_COMPLETED
                    && $record->due_date
                    && Carbon::parse($record->due_date)->isBefore(Carbon::today())
                ) {
                    return 'danger';
                }

                if (
                    $state === Pendiente::STATUS_PENDING
                    && $record->due_date
                    && Carbon::parse($record->due_date)->isToday()
                ) {
                    return 'warning';
                }

                return match ($state) {
                    Pendiente::STATUS_PENDING => 'yellow',
                    Pendiente::STATUS_IN_PROGRESS => 'info',
                    Pendiente::STATUS_COMPLETED => 'success',
                    Pendiente::STATUS_CANCELLED => 'gray',
                    default => 'secondary',
                };
            });

        if (Pendiente::supportsTechnicalBoard()) {
            $columns[] = TextColumn::make('service_order_number')
                ->label('OT')
                ->searchable()
                ->toggleable();

            $columns[] = TextColumn::make('workflow_stage')
                ->label('Tablero')
                ->badge()
                ->sortable()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getWorkflowStageLabel($state))
                ->color(fn (?string $state): string => match ($state) {
                    Pendiente::STAGE_POSTVENTA => 'gray',
                    Pendiente::STAGE_ALFREDO_REVIEW => 'warning',
                    Pendiente::STAGE_ASSIGNED => 'info',
                    Pendiente::STAGE_ACCEPTED => 'success',
                    Pendiente::STAGE_COMPLETED => 'success',
                    Pendiente::STAGE_CANCELLED => 'gray',
                    default => 'gray',
                });

            $columns[] = TextColumn::make('service_address')
                ->label('Direccion')
                ->wrap()
                ->searchable()
                ->getStateUsing(fn (Pendiente $record): string => $record->service_address ?: ($record->client?->full_address ?? 'Sin direccion'));

            $columns[] = TextColumn::make('neighborhood')
                ->label('Barrio')
                ->searchable()
                ->toggleable();

            $columns[] = TextColumn::make('operational_zone')
                ->label('Zona operativa')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getOperationalZoneOptions()[$state] ?? 'Sin zona');

            $columns[] = TextColumn::make('suggested_priority')
                ->label('Sug. Posventa')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getPriorityOptions()[$state] ?? 'Sin definir');

            $columns[] = TextColumn::make('reviewedBy.name')
                ->label('Reviso')
                ->placeholder('Pendiente');

            $columns[] = TextColumn::make('control_summary')
                ->label('Resumen de control')
                ->limit(150)
                ->wrap();

            $columns[] = TextColumn::make('technician_signature_name')
                ->label('Firma tecnico')
                ->badge()
                ->formatStateUsing(fn (?string $state, Pendiente $record): string => filled($state) || $record->technician_acknowledged
                    ? ($state ?: 'Firmado')
                    : 'Pendiente')
                ->color(fn (?string $state, Pendiente $record): string => filled($state) || $record->technician_acknowledged ? 'success' : 'gray');

            array_unshift($groups, self::workflowGroup(), Group::make('operational_zone')->label('Zona operativa'));

            array_unshift(
                $filters,
                SelectFilter::make('workflow_stage')->label('Tablero')->options(Pendiente::getWorkflowStageOptions()),
                SelectFilter::make('suggested_priority')->label('Prioridad Posventa')->options(Pendiente::getPriorityOptions()),
                SelectFilter::make('operational_zone')->label('Zona operativa')->options(Pendiente::getOperationalZoneOptions())
            );

            $filters[] = Filter::make('sin_firma')
                ->label('Sin firma tecnica')
                ->query(fn (Builder $query): Builder => $query->where(function (Builder $inner): Builder {
                    return $inner
                        ->whereNull('technician_signature_name')
                        ->orWhere('technician_acknowledged', false);
                }));
        }

        $columns[] = ImageColumn::make('visits.arrival_photo')
            ->label('Llegada')
            ->circular()
            ->disk('public')
            ->getStateUsing(fn (Pendiente $record): ?string => $record->latestVisit?->arrival_photo)
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = ImageColumn::make('visits.departure_photo')
            ->label('Salida')
            ->circular()
            ->disk('public')
            ->getStateUsing(fn (Pendiente $record): ?string => $record->latestVisit?->departure_photo)
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = TextColumn::make('description')
            ->label('Detalle')
            ->limit(50)
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = TextColumn::make('notes')
            ->label('Ultima novedad')
            ->limit(50)
            ->wrap()
            ->toggleable(isToggledHiddenByDefault: true);

        $actions = [
            Action::make('en_proceso')
                ->label('Tomar')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->visible(fn (Pendiente $record): bool => $record->status === Pendiente::STATUS_PENDING)
                ->form([
                    Textarea::make('notes')
                        ->label('Observacion')
                        ->rows(4),
                ])
                ->action(function (Pendiente $record, array $data): void {
                    $record->status = Pendiente::STATUS_IN_PROGRESS;
                    $record->user_id = auth()->id();

                    if (filled($data['notes'] ?? null)) {
                        $record->notes = $data['notes'];
                    }

                    $record->save();
                }),
            Action::make('finalizar')
                ->label('Cerrar')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (Pendiente $record): bool => $record->status !== Pendiente::STATUS_COMPLETED)
                ->form([
                    Textarea::make('notes')
                        ->label('Observacion de cierre')
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (Pendiente $record, array $data): void {
                    $record->status = Pendiente::STATUS_COMPLETED;
                    $record->notes = $data['notes'];
                    $record->completed_at = now();
                    $record->save();
                }),
            Action::make('ver_fotos')
                ->label('Ver fotos')
                ->visible(fn (Pendiente $record): bool => (bool) $record->visits_exists)
                ->modalContent(fn (Pendiente $record) => view('visits.modal', [
                    'visit' => $record->latestVisit,
                ])),
            Action::make('asignarme')
                ->label('Asignarme')
                ->color('info')
                ->visible(fn (Pendiente $record): bool => blank($record->user_id))
                ->action(function (Pendiente $record): void {
                    $record->user_id = auth()->id();
                    $record->save();
                }),
            EditAction::make(),
            Action::make('ver_visita')
                ->label('Visita')
                ->color('primary')
                ->visible(fn (Pendiente $record): bool => (bool) $record->visits_exists)
                ->url(fn (Pendiente $record): string => ServiceVisitResource::getUrl(
                    'index',
                    ['tableFilters[pendiente_id][value]' => $record->id],
                )),
        ];

        if (Pendiente::supportsTechnicalBoard()) {
            array_unshift($actions, Action::make('tablero_tecnico')
                ->label('Tablero')
                ->icon('heroicon-o-rectangle-group')
                ->color('primary')
                ->url(fn (Pendiente $record): string => \App\Filament\Resources\Pendientes\PendienteResource::getUrl(
                    'technical_board',
                    ['record' => $record],
                )));

            array_unshift($actions, Action::make('firmar')
                ->label('Firma tecnico')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->visible(fn (Pendiente $record): bool => filled($record->user_id) && ! $record->technician_acknowledged)
                ->form([
                    Checkbox::make('technician_acknowledged')
                        ->label('Confirmo tarea y herramientas')
                        ->required(),
                    TextInput::make('technician_signature_name')
                        ->label('Firma')
                        ->required(),
                    DatePicker::make('technician_signature_at')
                        ->label('Fecha de firma')
                        ->native(false),
                    Textarea::make('technician_signature_notes')
                        ->label('Observaciones')
                        ->rows(3),
                ])
                ->fillForm(fn (): array => [
                    'technician_acknowledged' => true,
                    'technician_signature_name' => auth()->user()?->name,
                    'technician_signature_at' => now()->toDateString(),
                ])
                ->action(function (Pendiente $record, array $data): void {
                    $record->fill($data);
                    $record->save();
                }));

            array_unshift($actions, Action::make('planificar')
                ->label('Planificar')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->visible(fn (Pendiente $record): bool => $record->status !== Pendiente::STATUS_COMPLETED)
                ->form([
                    Select::make('reviewed_by_user_id')
                        ->label('Revisado por')
                        ->relationship('reviewedBy', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('assigned_by_user_id')
                        ->label('Asignado por')
                        ->relationship('assignedBy', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('user_id')
                        ->label('Tecnico')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('priority')
                        ->label('Prioridad final')
                        ->options(Pendiente::getPriorityOptions())
                        ->required(),
                    Select::make('operational_zone')
                        ->label('Zona operativa')
                        ->options(Pendiente::getOperationalZoneOptions()),
                    TextInput::make('service_order_number')
                        ->label('Orden de trabajo'),
                    DatePicker::make('assigned_at')
                        ->label('Fecha de asignacion')
                        ->native(false),
                    Textarea::make('review_notes')
                        ->label('Revision / instrucciones')
                        ->rows(4)
                        ->required(),
                    CheckboxList::make('required_tools')
                        ->label('Herramientas requeridas')
                        ->options(fn (Pendiente $record): array => Pendiente::getToolOptionsForType($record->type))
                        ->columns(2),
                    Select::make('inventory_unit_ids')
                        ->label('Equipos / unidades')
                        ->options(fn (Pendiente $record): array => app(ProductoUnidadAssignmentService::class)->optionsForPendiente($record))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Reserva unidades serializadas para este pendiente.'),
                ])
                ->fillForm(fn (Pendiente $record): array => [
                    'reviewed_by_user_id' => $record->reviewed_by_user_id,
                    'assigned_by_user_id' => $record->assigned_by_user_id ?? auth()->id(),
                    'user_id' => $record->user_id,
                    'priority' => $record->priority,
                    'operational_zone' => $record->operational_zone,
                    'service_order_number' => $record->service_order_number,
                    'assigned_at' => $record->assigned_at,
                    'review_notes' => $record->review_notes,
                    'required_tools' => $record->required_tools,
                    'inventory_unit_ids' => $record->activeUnitAssignments()
                        ->pluck('producto_unidad_id')
                        ->map(fn ($id): int => (int) $id)
                        ->all(),
                ])
                ->action(function (Pendiente $record, array $data): void {
                    $inventoryUnitIds = collect($data['inventory_unit_ids'] ?? [])
                        ->filter(fn ($value): bool => filled($value))
                        ->map(fn ($value): int => (int) $value)
                        ->values()
                        ->all();

                    unset($data['inventory_unit_ids']);

                    $record->fill($data);
                    $record->save();

                    app(ProductoUnidadAssignmentService::class)->syncForPendiente(
                        $record,
                        $inventoryUnitIds,
                        auth()->user(),
                        'Reserva actualizada desde planificacion.',
                    );
                }));
        }

        return $table
            ->columns($columns)
            ->groups($groups)
            ->defaultGroup(Pendiente::supportsTechnicalBoard() ? self::workflowGroup() : self::statusGroup())
            ->filters($filters)
            ->headerActions([
                Action::make('exportar')
                    ->label('Generar informe')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => app(PendienteReportService::class)->export()),
                Action::make('pdf')
                    ->label('Informe PDF')
                    ->icon('heroicon-o-document')
                    ->action(fn () => app(PendienteReportService::class)->pdf()),
                Action::make('reporte_filtrado')
                    ->label('Reporte personalizado')
                    ->form([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pendiente',
                                'in_progress' => 'En proceso',
                                'completed' => 'Completado',
                            ])
                            ->placeholder('Todos'),
                        Select::make('user_id')
                            ->label('Tecnico')
                            ->relationship('user', 'name')
                            ->searchable(),
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('to')->label('Hasta'),
                    ])
                    ->action(fn (array $data) => app(PendienteReportService::class)->generate($data)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->actions($actions)
            ->recordClasses(fn (Pendiente $record): ?string => $record->status !== Pendiente::STATUS_COMPLETED && $record->due_date && $record->due_date < now()
                ? 'erp-table-row-overdue'
                : ($record->priority === 'alta' ? 'erp-table-row-priority' : null))
            ->defaultSort('due_date', 'asc');
    }

    protected static function workflowGroup(): Group
    {
        return Group::make('workflow_stage')
            ->label('Tablero tecnico')
            ->titlePrefixedWithLabel(false)
            ->collapsible()
            ->getTitleFromRecordUsing(fn (Pendiente $record): string => Pendiente::getWorkflowStageLabel($record->workflow_stage))
            ->orderQueryUsing(function (Builder $query, string $direction): Builder {
                $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

                return $query->orderByRaw("
                    case workflow_stage
                        when 'postventa' then 1
                        when 'alfredo_review' then 2
                        when 'assigned' then 3
                        when 'accepted' then 4
                        when 'completed' then 5
                        when 'cancelled' then 6
                        else 99
                    end {$direction}
                ");
            });
    }

    protected static function statusGroup(): Group
    {
        return Group::make('status')
            ->label('Estado')
            ->titlePrefixedWithLabel(false)
            ->collapsible()
            ->getTitleFromRecordUsing(fn (Pendiente $record): string => Pendiente::getStatusLabel($record->status))
            ->orderQueryUsing(function (Builder $query, string $direction): Builder {
                $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

                return $query->orderByRaw("
                    case status
                        when 'pending' then 1
                        when 'in_progress' then 2
                        when 'completed' then 3
                        when 'cancelled' then 4
                        else 99
                    end {$direction}
                ");
            });
    }
}
