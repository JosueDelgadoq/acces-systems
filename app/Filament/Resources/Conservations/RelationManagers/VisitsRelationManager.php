<?php

namespace App\Filament\Resources\Conservations\RelationManagers;

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

class VisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'visits';

    protected static ?string $title = 'Historial de servicios';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                DatePicker::make('date')
                    ->label('Fecha del servicio')
                    ->required(),

                Select::make('technician_id')
                    ->label('Técnico')
                    ->relationship('technician', 'name')
                    ->searchable()
                    ->required(),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'done' => 'Realizado',
                        'rescheduled' => 'Reprogramado',
                    ])
                    ->default('pending')
                    ->required(),

                Textarea::make('notes')
                    ->label('Detalle del servicio')
                    ->placeholder('Ej: Se realizó mantenimiento, limpieza, ajuste, etc...')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                TextColumn::make('technician.name')
                    ->label('Técnico')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'done' => 'success',
                        'rescheduled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('notes')
                    ->label('Detalle')
                    ->limit(40),

            ])
            ->defaultSort('date', 'desc')

            ->headerActions([
                CreateAction::make()
                    ->label('➕ Registrar servicio'),
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