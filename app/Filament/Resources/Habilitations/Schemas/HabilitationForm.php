<?php

namespace App\Filament\Resources\Habilitations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HabilitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->label('Cliente')
                    ->searchable()
                    ->preload(),
                TextInput::make('equipment')
                    ->required()
                    ->label('Equipo'),
                Select::make('status')
                    ->required()
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'documentacion' => 'En Documentación',
                        'enviado_gestor' => 'Enviado al Gestor',
                        'en_tramite' => 'En Trámite',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                        'archivado' => 'Archivado',
                    ])
                    ->default('pendiente'),
                Select::make('doc_completa')
                    ->label('Documentación Completa')
                    ->options([
                        '0' => 'No',
                        '1' => 'Sí',
                    ])
                    ->default('0'),
                DatePicker::make('fecha_envio_gestor')
                    ->label('Fecha Envío al Gestor'),
                DatePicker::make('fecha_presentacion')
                    ->label('Fecha Presentación'),
                DatePicker::make('proxima_gestion')
                    ->label('Próxima Gestión'),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }
}

