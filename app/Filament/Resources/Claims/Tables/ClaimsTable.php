<?php

namespace App\Filament\Resources\Claims\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

class ClaimsTable
{
    public static function configure(Table $table): Table
{
    return $table
        ->columns([

            // 👤 CLIENTE
            TextColumn::make('client.name')
                ->label('Cliente')
                ->searchable()
                ->weight('bold'),

            // 📝 TÍTULO
            TextColumn::make('title')
                ->label('Reclamo')
                ->searchable()
                ->limit(40)
                ->tooltip(fn ($record) => $record->title),

            // 🚦 ESTADO (COLOR)
            BadgeColumn::make('status')
                ->label('Estado')
                ->colors([
                    'danger' => 'abierto',
                    'warning' => 'en_proceso',
                    'success' => 'cerrado',
                ])
                ->formatStateUsing(fn ($state) => match ($state) {
                    'abierto' => 'Abierto',
                    'en_proceso' => 'En proceso',
                    'cerrado' => 'Cerrado',
                    default => $state,
                }),

            // 👨‍🔧 TÉCNICO
            TextColumn::make('technician.name')
                ->label('Técnico')
                ->placeholder('Sin asignar'),

            // 📅 VISITA
            TextColumn::make('scheduled_visit')
                ->label('Visita')
                ->date()
                ->color(fn ($record) =>
                    $record->scheduled_visit && $record->scheduled_visit < now()
                        ? 'danger'
                        : 'success'
                ),

            // ⏱️ CIERRE
            TextColumn::make('closed_at')
                ->label('Cierre')
                ->since()
                ->placeholder('-'),

            // 📆 CREACIÓN
            TextColumn::make('created_at')
                ->label('Creado')
                ->since()
                ->color('gray'),
        ])

        ->defaultSort('created_at', 'desc')

        ->striped(); // 💥 look moderno
    }
}
