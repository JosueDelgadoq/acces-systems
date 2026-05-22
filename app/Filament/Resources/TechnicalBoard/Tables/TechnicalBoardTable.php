<?php

namespace App\Filament\Resources\TechnicalBoard\Tables;

use App\Filament\Resources\ServiceVisits\ServiceVisitResource;
use App\Filament\Resources\TechnicalBoard\TechnicalBoardResource;
use App\Models\Pendiente;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TechnicalBoardTable
{
    public static function configure(Table $table): Table
    {
        $columns = [
            TextColumn::make('workflow_stage')
                ->label('Etapa')
                ->badge()
                ->sortable()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getWorkflowStageLabel($state)),
            TextColumn::make('status')
                ->label('Estado')
                ->badge()
                ->sortable()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getStatusLabel($state)),
            TextColumn::make('due_date')
                ->label('Programada')
                ->formatStateUsing(fn ($state): string => $state ? Carbon::parse($state)->format('d/m/Y') : 'A programar'),
            TextColumn::make('client.name')
                ->label('Cliente')
                ->searchable()
                ->sortable(),
            TextColumn::make('client_availability')
                ->label('Disponibilidad')
                ->wrap()
                ->toggleable()
                ->visible(Pendiente::hasColumn('client_availability')),
            TextColumn::make('type')
                ->label('Tipo')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getTypeOptions()[$state] ?? (string) $state),
            TextColumn::make('service_address')
                ->label('Direccion')
                ->wrap()
                ->searchable(),
            TextColumn::make('operational_zone')
                ->label('Zona')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getOperationalZoneOptions()[$state] ?? 'Sin zona'),
            TextColumn::make('priority')
                ->label('Prioridad')
                ->badge()
                ->formatStateUsing(fn (?string $state): string => Pendiente::getPriorityOptions()[$state] ?? 'Sin definir'),
            TextColumn::make('estimated_time')
                ->label('Tiempo estimado')
                ->toggleable()
                ->visible(Pendiente::hasColumn('estimated_time')),
            TextColumn::make('initial_diagnosis')
                ->label('Diagnostico inicial')
                ->limit(50)
                ->wrap()
                ->toggleable()
                ->visible(Pendiente::hasColumn('initial_diagnosis')),
            TextColumn::make('possible_spare_parts')
                ->label('Repuestos posibles')
                ->limit(50)
                ->wrap()
                ->toggleable()
                ->visible(Pendiente::hasColumn('possible_spare_parts')),
            TextColumn::make('user.name')
                ->label('Tecnico')
                ->placeholder('Sin asignar'),
            TextColumn::make('required_tools')
                ->label('Herramientas')
                ->formatStateUsing(function ($state): string {
                    $tools = collect($state)->filter()->values();

                    if ($tools->isEmpty()) {
                        return 'Sin definir';
                    }

                    return $tools->implode(', ');
                })
                ->wrap()
                ->toggleable(),
            TextColumn::make('technician_signature_name')
                ->label('Firma previa')
                ->badge()
                ->formatStateUsing(fn (?string $state, Pendiente $record): string => filled($state) || $record->technician_acknowledged ? ($state ?: 'Firmada') : 'Pendiente'),
            TextColumn::make('post_visit_signature_name')
                ->label('Firma final')
                ->badge()
                ->formatStateUsing(fn (?string $state, Pendiente $record): string => Pendiente::hasColumn('post_visit_signature_name') && (filled($state) || $record->post_visit_acknowledged) ? ($state ?: 'Firmada') : 'Pendiente')
                ->visible(Pendiente::hasColumn('post_visit_signature_name')),
        ];

        return $table
            ->columns($columns)
            ->groups([
                Group::make('workflow_stage')->label('Etapa'),
                Group::make('operational_zone')->label('Zona'),
                Group::make('user.name')->label('Tecnico'),
            ])
            ->defaultGroup(Group::make('workflow_stage')->label('Etapa'))
            ->filters([
                SelectFilter::make('workflow_stage')
                    ->label('Etapa')
                    ->options(Pendiente::getWorkflowStageOptions()),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(Pendiente::getStatusOptions()),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Pendiente::getTypeOptions()),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options(Pendiente::getPriorityOptions()),
                SelectFilter::make('operational_zone')
                    ->label('Zona')
                    ->options(Pendiente::getOperationalZoneOptions()),
                SelectFilter::make('user_id')
                    ->label('Tecnico')
                    ->relationship('user', 'name'),
                Filter::make('sin_firma_previa')
                    ->label('Sin firma previa')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $inner): Builder {
                        return $inner
                            ->whereNull('technician_signature_name')
                            ->orWhere('technician_acknowledged', false);
                    })),
                Filter::make('sin_firma_final')
                    ->label('Sin firma final')
                    ->query(fn (Builder $query): Builder => Pendiente::hasColumn('post_visit_signature_name')
                        ? $query->where(function (Builder $inner): Builder {
                            return $inner
                                ->whereNull('post_visit_signature_name')
                                ->orWhere('post_visit_acknowledged', false);
                        })
                        : $query),
            ])
            ->actions([
                Action::make('abrir_tablero')
                    ->label('Abrir')
                    ->icon('heroicon-o-rectangle-group')
                    ->color('primary')
                    ->url(fn (Pendiente $record): string => TechnicalBoardResource::getUrl('edit', ['record' => $record])),
                Action::make('firma_previa')
                    ->label('Firma previa')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (Pendiente $record): bool => filled($record->user_id) && ! $record->technician_acknowledged)
                    ->form([
                        Checkbox::make('technician_acknowledged')
                            ->label('Confirmo herramientas y tarea')
                            ->required(),
                        TextInput::make('technician_signature_name')
                            ->label('Firma previa')
                            ->required(),
                        DatePicker::make('technician_signature_at')
                            ->label('Fecha')
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
                    }),
                Action::make('tomar')
                    ->label('Tomar')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (Pendiente $record): bool => $record->status === Pendiente::STATUS_PENDING)
                ->action(function (Pendiente $record): void {
                    $record->status = Pendiente::STATUS_IN_PROGRESS;
                    $record->user_id = auth()->id();
                    $record->save();
                }),
                Action::make('firma_final')
                    ->label('Firma final')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Pendiente $record): bool => Pendiente::hasColumn('post_visit_signature_name') && $record->status === Pendiente::STATUS_COMPLETED && ! $record->post_visit_acknowledged)
                    ->form([
                        Checkbox::make('post_visit_acknowledged')
                            ->label('Confirmo cierre de la visita')
                            ->required(),
                        TextInput::make('post_visit_signature_name')
                            ->label('Firma final')
                            ->required(),
                        DatePicker::make('post_visit_signature_at')
                            ->label('Fecha')
                            ->native(false),
                        Textarea::make('post_visit_signature_notes')
                            ->label('Observaciones finales')
                            ->rows(3),
                    ])
                    ->fillForm(fn (): array => [
                        'post_visit_acknowledged' => true,
                        'post_visit_signature_name' => auth()->user()?->name,
                        'post_visit_signature_at' => now()->toDateString(),
                    ])
                    ->action(function (Pendiente $record, array $data): void {
                        $record->fill($data);
                        $record->save();
                    }),
                Action::make('cerrar')
                    ->label('Cerrar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Pendiente $record): bool => $record->status !== Pendiente::STATUS_COMPLETED)
                    ->form([
                        Textarea::make('notes')
                            ->label('Novedad de cierre')
                            ->rows(4)
                            ->required(),
                        Textarea::make('control_summary')
                            ->label('Resumen de control')
                            ->rows(4),
                    ])
                    ->action(function (Pendiente $record, array $data): void {
                        $record->status = Pendiente::STATUS_COMPLETED;
                        $record->notes = $data['notes'];
                        $record->control_summary = $data['control_summary'] ?? $record->control_summary;
                        $record->completed_at = now();
                        $record->save();
                    }),
                Action::make('visita')
                    ->label('Visita')
                    ->color('gray')
                    ->visible(fn (Pendiente $record): bool => (bool) $record->visits_exists)
                    ->url(fn (Pendiente $record): string => ServiceVisitResource::getUrl(
                        'index',
                        ['tableFilters[pendiente_id][value]' => $record->id],
                    )),
                EditAction::make()
                    ->label('Editar'),
            ])
            ->recordClasses(fn (Pendiente $record): ?string => $record->status !== Pendiente::STATUS_COMPLETED && $record->due_date && $record->due_date < now()
                ? 'erp-table-row-overdue'
                : ($record->priority === 'alta' ? 'erp-table-row-priority' : null))
            ->defaultSort('due_date', 'asc');
    }
}
