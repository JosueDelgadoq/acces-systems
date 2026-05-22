<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Models\Seguimiento;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeguimientosRelationManager extends RelationManager
{
    protected static string $relationship = 'seguimientos';

    protected static ?string $title = 'Seguimiento comercial';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('estado')
                    ->label('Estado')
                    ->options(Seguimiento::getStatusOptions())
                    ->default(Seguimiento::STATUS_PENDIENTE)
                    ->required(),

                Select::make('comercial_id')
                    ->label('Comercial')
                    ->relationship('comercial', 'name')
                    ->default(fn (): ?int => auth()->id())
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('medio_contacto')
                    ->label('Medio')
                    ->options(Seguimiento::getMedioContactoOptions())
                    ->searchable()
                    ->required(),

                DatePicker::make('fecha_contacto')
                    ->label('Fecha de contacto')
                    ->displayFormat('d/m/Y')
                    ->native(false),

                Textarea::make('resultado')
                    ->label('Resultado')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('proxima_accion')
                    ->label('Proxima accion')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('fecha_proxima_accion')
                    ->label('Fecha proxima accion')
                    ->displayFormat('d/m/Y')
                    ->native(false),

                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('proxima_accion')
            ->defaultSort('fecha_proxima_accion', 'asc')
            ->columns([
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Seguimiento::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Seguimiento::STATUS_PENDIENTE => 'warning',
                        Seguimiento::STATUS_COMPLETADO => 'success',
                        Seguimiento::STATUS_CANCELADO => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('fecha_proxima_accion')
                    ->label('Compromiso')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(function ($state, Seguimiento $record): string {
                        if (! $record->fecha_proxima_accion || $record->estado !== Seguimiento::STATUS_PENDIENTE) {
                            return 'gray';
                        }

                        return $record->fecha_proxima_accion->isPast() ? 'danger' : 'warning';
                    }),

                TextColumn::make('proxima_accion')
                    ->label('Proxima accion')
                    ->wrap()
                    ->limit(60),

                TextColumn::make('medio_contacto')
                    ->label('Medio')
                    ->formatStateUsing(fn (?string $state): string => Seguimiento::getMedioContactoOptions()[$state] ?? 'Sin definir'),

                TextColumn::make('comercial.name')
                    ->label('Comercial')
                    ->toggleable(),

                TextColumn::make('fecha_contacto')
                    ->label('Ultimo contacto')
                    ->date('d/m/Y')
                    ->placeholder('Sin registrar'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar seguimiento'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
