<?php

namespace App\Filament\Resources\Clients\ClientResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class ClaimsRelationManager extends RelationManager
{
    protected static string $relationship = 'claims';

    protected static ?string $title = 'Reclamos';

public function form(Schema $schema): Schema
{
    return $schema->schema([

        Forms\Components\TextInput::make('title')
            ->label('Título')
            ->required(),

        Forms\Components\Textarea::make('description')
            ->label('Descripción')
            ->columnSpanFull(),

        Forms\Components\Select::make('status')
            ->label('Estado')
            ->options([
                'nuevo' => 'Nuevo',
                'asignado' => 'Asignado',
                'en_proceso' => 'En proceso',
                'resuelto' => 'Resuelto',
                'cerrado' => 'Cerrado',
            ])
            ->default('nuevo')
            ->required(),

        Forms\Components\Select::make('technician_id')
            ->label('Técnico')
            ->relationship('technician', 'name')
            ->searchable()
            ->preload(),

        Forms\Components\DatePicker::make('scheduled_visit')
            ->label('Fecha de visita'),

        Forms\Components\Textarea::make('resolution')
            ->label('Resolución')
            ->columnSpanFull(),

    ]);
}

public function table(Table $table): Table
{
    return $table
        ->columns([

            Tables\Columns\TextColumn::make('title')
                ->label('Reclamo')
                ->searchable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'danger' => 'nuevo',
                    'warning' => 'asignado',
                    'info' => 'en_proceso',
                    'success' => 'resuelto',
                    'gray' => 'cerrado',
                ]),

            Tables\Columns\TextColumn::make('technician.name')
                ->label('Técnico'),

            Tables\Columns\TextColumn::make('scheduled_visit')
                ->label('Visita')
                ->date(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Creado')
                ->dateTime(),

        ]);
}
}