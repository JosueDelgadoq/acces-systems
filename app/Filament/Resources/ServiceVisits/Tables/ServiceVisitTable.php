<?php

namespace App\Filament\Resources\ServiceVisits\Tables;

use App\Filament\Resources\Pendientes\PendienteResource;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceVisitTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->groups([
                self::typeGroup(),
                Group::make('technician.name')
                    ->label('Tecnico'),
            ])
            ->defaultGroup(self::typeGroup())
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('visit_type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => ServiceVisit::getTypeLabel($state)),
                TextColumn::make('pendiente.type')
                    ->label('Servicio')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Pendiente::getTypeOptions()[$state] ?? (string) $state)
                    ->placeholder('Legacy'),
                TextColumn::make('pendiente.service_order_number')
                    ->label('OT')
                    ->searchable()
                    ->placeholder('Sin OT'),
                TextColumn::make('pendiente.client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin cliente'),
                TextColumn::make('technician.name')
                    ->label('Tecnico')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin tecnico'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => ServiceVisit::getStatusLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        ServiceVisit::STATUS_PENDING => 'warning',
                        ServiceVisit::STATUS_IN_PROGRESS => 'info',
                        ServiceVisit::STATUS_COMPLETED => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('pendiente.priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Pendiente::getPriorityOptions()[$state] ?? 'Sin definir')
                    ->color(fn (?string $state): string => match ($state) {
                        'alta' => 'danger',
                        'media' => 'warning',
                        'baja' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('pendiente.due_date')
                    ->label('Programada')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('arrival_time')
                    ->label('Llegada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('departure_time')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                ImageColumn::make('arrival_photo')
                    ->label('Foto llegada')
                    ->disk('public')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('departure_photo')
                    ->label('Foto salida')
                    ->disk('public')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pendiente.service_address')
                    ->label('Direccion')
                    ->wrap()
                    ->searchable()
                    ->placeholder('Sin direccion')
                    ->toggleable(),
                TextColumn::make('report')
                    ->label('Informe')
                    ->limit(90)
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ServiceVisit::getStatusOptions()),
                SelectFilter::make('visit_type')
                    ->label('Tipo operativo')
                    ->options(ServiceVisit::getTypeOptions()),
                SelectFilter::make('technician_id')
                    ->label('Tecnico')
                    ->relationship('technician', 'name'),
                SelectFilter::make('pendiente_type')
                    ->label('Tipo de pendiente')
                    ->options(Pendiente::getTypeOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('pendiente', fn (Builder $pendienteQuery): Builder => $pendienteQuery->where('type', $value));
                    }),
            ])
            ->actions([
                Action::make('abrir_pendiente')
                    ->label('Pendiente')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->visible(fn (ServiceVisit $record): bool => filled($record->pendiente_id))
                    ->url(function (ServiceVisit $record): ?string {
                        if (! $record->pendiente_id) {
                            return null;
                        }

                        $route = Pendiente::supportsTechnicalBoard() ? 'technical_board' : 'edit';

                        return PendienteResource::getUrl($route, ['record' => $record->pendiente_id]);
                    }),
                EditAction::make()
                    ->label('Detalle'),
            ])
            ->defaultSort('id', 'desc');
    }

    protected static function typeGroup(): Group
    {
        return Group::make('visit_type')
            ->label('Tipo operativo')
            ->titlePrefixedWithLabel(false)
            ->getTitleFromRecordUsing(fn (ServiceVisit $record): string => ServiceVisit::getTypeLabel($record->visit_type));
    }
}
