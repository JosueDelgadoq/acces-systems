<?php

namespace App\Filament\Resources\ServiceVisits\Schemas;

use App\Filament\Resources\Pendientes\PendienteResource;
use App\Models\Pendiente;
use App\Models\ServiceVisit;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ServiceVisitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Asignacion operativa')
                ->description('La visita se crea y se asigna automaticamente desde el pendiente tecnico.')
                ->schema([
                    Select::make('visit_type')
                        ->label('Tipo operativo')
                        ->options(ServiceVisit::getTypeOptions())
                        ->disabled(),
                    Select::make('technician_id')
                        ->label('Tecnico asignado')
                        ->relationship('technician', 'name')
                        ->searchable()
                        ->disabled(),
                    Select::make('status')
                        ->label('Estado')
                        ->options(ServiceVisit::getStatusOptions())
                        ->disabled(),
                    Placeholder::make('pendiente_link')
                        ->label('Pendiente vinculado')
                        ->content(function (?ServiceVisit $record): HtmlString {
                            if (! $record?->pendiente_id) {
                                return new HtmlString('Visita legacy sin pendiente vinculado.');
                            }

                            $route = Pendiente::supportsTechnicalBoard() ? 'technical_board' : 'edit';
                            $url = PendienteResource::getUrl($route, ['record' => $record->pendiente_id]);

                            return new HtmlString('<a href="' . e($url) . '" class="text-primary-600 underline">Abrir pendiente #' . e((string) $record->pendiente_id) . '</a>');
                        }),
                    Placeholder::make('client_summary')
                        ->label('Cliente')
                        ->content(fn (?ServiceVisit $record): string => $record?->pendiente?->client?->name ?? $record?->claim?->client?->name ?? 'Sin cliente'),
                    Placeholder::make('service_order_number')
                        ->label('Orden de trabajo')
                        ->content(fn (?ServiceVisit $record): string => $record?->pendiente?->service_order_number ?? 'Sin OT'),
                    Placeholder::make('priority')
                        ->label('Prioridad')
                        ->content(fn (?ServiceVisit $record): string => $record?->pendiente ? (Pendiente::getPriorityOptions()[$record->pendiente->priority] ?? 'Sin definir') : 'Sin definir'),
                    Placeholder::make('address')
                        ->label('Direccion')
                        ->content(fn (?ServiceVisit $record): string => $record?->pendiente?->service_address ?? $record?->pendiente?->client?->full_address ?? 'Sin direccion')
                        ->columnSpanFull(),
                    Placeholder::make('work_order')
                        ->label('Instrucciones')
                        ->content(fn (?ServiceVisit $record): string => $record?->pendiente?->work_order ?? $record?->pendiente?->review_notes ?? 'Sin instrucciones')
                        ->columnSpanFull(),
                    Placeholder::make('assigned_units')
                        ->label('Equipos vinculados')
                        ->content(function (?ServiceVisit $record): HtmlString {
                            if (! $record) {
                                return new HtmlString('Sin visita cargada.');
                            }

                            $assignments = $record->unitAssignments()
                                ->with('unit.variante.producto')
                                ->orderBy('id')
                                ->get();

                            if ($assignments->isEmpty()) {
                                return new HtmlString('Sin equipos vinculados.');
                            }

                            $lines = $assignments
                                ->map(function ($assignment): string {
                                    $unit = $assignment->unit;

                                    if (! $unit) {
                                        return 'Unidad no disponible en catalogo';
                                    }

                                    return collect([
                                        $unit->codigo_barra,
                                        $unit->variante?->producto?->nombre,
                                        $unit->variante?->modelo,
                                        $assignment->status_label,
                                    ])
                                        ->filter(fn ($value): bool => filled($value))
                                        ->map(fn ($value): string => e((string) $value))
                                        ->implode(' | ');
                                })
                                ->implode('<br>');

                            return new HtmlString($lines);
                        })
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ]),
            Section::make('Ejecucion')
                ->description('Tiempos, evidencia y cierre reportados desde la app del tecnico.')
                ->schema([
                    DateTimePicker::make('arrival_time')
                        ->label('Inicio en sitio')
                        ->disabled(),
                    DateTimePicker::make('departure_time')
                        ->label('Cierre de visita')
                        ->disabled(),
                    FileUpload::make('arrival_photo')
                        ->label('Foto de llegada')
                        ->image()
                        ->disk('public')
                        ->directory('visits')
                        ->disabled(),
                    FileUpload::make('departure_photo')
                        ->label('Foto de salida')
                        ->image()
                        ->disk('public')
                        ->directory('visits')
                        ->disabled(),
                    Textarea::make('report')
                        ->label('Informe tecnico')
                        ->rows(8)
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ]),
            Section::make('Trazabilidad')
                ->description('Linea de tiempo operativa generada desde aceptacion, traslado, inicio y cierre.')
                ->schema([
                    View::make('filament.service-visits.timeline')
                        ->columnSpanFull(),
                ])
                ->visible(fn (?ServiceVisit $record): bool => filled($record))
                ->columnSpanFull(),
        ]);
    }
}
