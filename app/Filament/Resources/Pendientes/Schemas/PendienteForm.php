<?php

namespace App\Filament\Resources\Pendientes\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;

class PendienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name')
                ->searchable()
                ->required(),

            Select::make('user_id')
                ->label('Asignado a')
                ->relationship('user', 'name')
                ->searchable()
                ->nullable(),

            Select::make('type')
                ->options([
                    'reclamo' => 'Reclamo',
                    'instalacion' => 'Instalación',
                    'desinstalacion' => 'Desinstalación',
                    'soporte' => 'Cambio de bateria',
                    'diagnostico' => 'Visita técnica de diagnóstico',
                        'mantenimiento' => 'Visita técnica de mantenimiento',
                        'reparacion' => 'Reparación',
                        'presupuesto' => 'Presupuestar',
                ])
                ->required(),

            Select::make('priority')
                ->label('Prioridad')
                ->options([
                    'baja' => 'Baja',
                    'media' => 'Media',
                    'alta' => 'Alta',
                ])
                ->default('media')
                ->required(),

                Hidden::make('source')
                ->default('manual'),

            DatePicker::make('due_date')
                    ->label('Fecha programada')
                    ->nullable()
                    ->displayFormat('d/m/Y') // 👈 lo que ve el usuario
                    ->format('Y-m-d') // 👈 lo que guarda
                    ->native(false),

                

            Select::make('status')
                ->label('Estado')
                ->options([
                    'pending' => 'Pendiente',
                    'in_progress' => 'En proceso',
                    'completed' => 'Finalizado',
                    'cancelled' => 'Cancelado',
                ])
                ->default('pending')
                ->required(),
                

            Textarea::make('description')
                ->label('Descripción')
                ->required()
                ->columnSpanFull(),

            Textarea::make('notes')
                ->label('Observaciones')
                ->columnSpanFull(),
                
        ]);
        
    }
    
}