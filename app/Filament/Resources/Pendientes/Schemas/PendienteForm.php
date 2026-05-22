<?php

namespace App\Filament\Resources\Pendientes\Schemas;

use App\Models\Pendiente;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PendienteForm
{
    public static function configure(Schema $schema): Schema
    {
        $components = [
            Section::make('Gestion del pendiente')
                ->description('Carga base del caso y seguimiento general.')
                ->schema([
                    Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('user_id')
                        ->label('Asignado a')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('type')
                        ->label('Tipo de tarea')
                        ->options(Pendiente::getTypeOptions())
                        ->required(),
                    Select::make('priority')
                        ->label('Prioridad')
                        ->options(Pendiente::getPriorityOptions())
                        ->default('media')
                        ->required(),
                    Hidden::make('source')
                        ->default('manual')
                        ->dehydrated(fn (): bool => Pendiente::hasColumn('source')),
                    DatePicker::make('due_date')
                        ->label('Fecha programada (sugerida)')
                        ->nullable()
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->placeholder('Seleccionar fecha')
                        ->closeOnDateSelection(),
                    DatePicker::make('performed_at')
                        ->label('Fecha real de ejecucion')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->closeOnDateSelection(),
                    TextInput::make('remito')
                        ->label('N de remito')
                        ->placeholder('Ej: 07-6231')
                        ->maxLength(7)
                        ->regex('/^\d{2}-\d{4}$/')
                        ->validationMessages([
                            'regex' => 'Formato invalido. Usar: 00-0000',
                        ])
                        ->nullable(),
                    ToggleButtons::make('status')
                        ->label('Estado actual')
                        ->options(Pendiente::getStatusOptions())
                        ->default(Pendiente::STATUS_PENDING)
                        ->colors([
                            Pendiente::STATUS_PENDING => 'warning',
                            Pendiente::STATUS_IN_PROGRESS => 'info',
                            Pendiente::STATUS_COMPLETED => 'success',
                            Pendiente::STATUS_CANCELLED => 'gray',
                        ])
                        ->icons([
                            Pendiente::STATUS_PENDING => 'heroicon-m-clock',
                            Pendiente::STATUS_IN_PROGRESS => 'heroicon-m-play',
                            Pendiente::STATUS_COMPLETED => 'heroicon-m-check-circle',
                            Pendiente::STATUS_CANCELLED => 'heroicon-m-x-circle',
                        ])
                        ->inline()
                        ->grouped()
                        ->columns([
                            'sm' => 2,
                            'xl' => 4,
                        ])
                        ->columnSpanFull()
                        ->required(),
                    Textarea::make('description')
                        ->label('Detalle del caso')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Novedad actual')
                        ->rows(4)
                        ->helperText('Se agrega automaticamente a la historia del pendiente.')
                        ->columnSpanFull(),
                    Textarea::make('control_summary')
                        ->label('Resumen de control')
                        ->rows(5)
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Pendiente $record): bool => Pendiente::hasColumn('control_summary') && filled($record?->control_summary))
                        ->helperText('Solo lectura. Este resumen se completa desde el tablero tecnico.')
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ])
                ->columnSpanFull(),

            Section::make('Circuito rapido')
                ->description('Referencia corta para operar el pendiente sin perder trazabilidad.')
                ->schema([
                    View::make('filament.pendientes.guide')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];

        if (Pendiente::supportsTechnicalBoard()) {
            $components[] = Section::make('Tablero tecnico')
                ->description('El circuito tecnico se gestiona en una pantalla separada para mantener esta edicion simple.')
                ->schema([
                    Select::make('suggested_priority')
                        ->label('Prioridad sugerida por Posventa')
                        ->options(Pendiente::getPriorityOptions())
                        ->default('media'),
                    TextInput::make('service_address')
                        ->label('Direccion de servicio')
                        ->maxLength(255),
                    TextInput::make('client_availability')
                        ->label('Disponibilidad del cliente')
                        ->maxLength(255)
                        ->placeholder('Ej: de 10 a 13 hs / solo por la tarde')
                        ->visible(fn (): bool => Pendiente::hasColumn('client_availability')),
                    TextInput::make('neighborhood')
                        ->label('Barrio')
                        ->maxLength(255),
                    Select::make('operational_zone')
                        ->label('Zona operativa')
                        ->options(Pendiente::getOperationalZoneOptions())
                        ->searchable(),
                    Placeholder::make('technical_board_link')
                        ->label('Abrir circuito tecnico')
                        ->content(function (?Pendiente $record): HtmlString {
                            if (! $record) {
                                return new HtmlString('Guarda el pendiente para abrir el tablero tecnico.');
                            }

                            $url = \App\Filament\Resources\Pendientes\PendienteResource::getUrl('technical_board', [
                                'record' => $record,
                            ]);

                            return new HtmlString(
                                '<div class="erp-link-card">' .
                                '<div class="erp-link-card-copy">Planificacion, firma tecnica y control final ahora viven en una pantalla dedicada.</div>' .
                                '<a href="' . e($url) . '" class="erp-link-button">Abrir tablero tecnico</a>' .
                                '</div>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ])
                ->columnSpanFull();
        } else {
            $components[] = Section::make('Tablero tecnico')
                ->schema([
                    Placeholder::make('technical_board_pending')
                        ->label('Migracion pendiente')
                        ->content('Cuando agregues manualmente las columnas nuevas de pendientes, este tablero tecnico se habilita automaticamente.'),
                ])
                ->columnSpanFull();
        }

        $components[] = Section::make('Historia y seguimiento')
            ->description('Secuencia completa del pendiente y trazabilidad de cambios.')
            ->schema([
                View::make('filament.pendientes.history')
                    ->columnSpanFull(),
            ])
            ->visible(fn (?Pendiente $record): bool => filled($record))
            ->columnSpanFull();

        return $schema->schema($components);
    }
}
