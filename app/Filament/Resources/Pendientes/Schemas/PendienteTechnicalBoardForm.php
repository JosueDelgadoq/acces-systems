<?php

namespace App\Filament\Resources\Pendientes\Schemas;

use App\Models\Pendiente;
use App\Services\ProductoUnidadAssignmentService;
use Carbon\Carbon;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PendienteTechnicalBoardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Resumen del caso')
                ->description('Lectura rapida del pedido original de Posventa.')
                ->schema([
                    Placeholder::make('client_summary')
                        ->label('Cliente')
                        ->content(fn (?Pendiente $record): string => $record?->client?->name ?? 'Sin cliente'),
                    Placeholder::make('type_summary')
                        ->label('Tipo de tarea')
                        ->content(fn (?Pendiente $record): string => Pendiente::getTypeOptions()[$record?->type] ?? 'Sin tipo'),
                    Placeholder::make('posventa_priority')
                        ->label('Prioridad sugerida por Posventa')
                        ->content(fn (?Pendiente $record): string => Pendiente::getPriorityOptions()[$record?->suggested_priority] ?? 'Sin definir'),
                    Placeholder::make('workflow_summary')
                        ->label('Etapa actual')
                        ->content(fn (?Pendiente $record): string => Pendiente::getWorkflowStageLabel($record?->workflow_stage)),
                    Placeholder::make('address_summary')
                        ->label('Direccion de servicio')
                        ->content(fn (?Pendiente $record): string => $record?->service_address ?: ($record?->client?->full_address ?? 'Sin direccion')),
                    Placeholder::make('availability_summary')
                        ->label('Disponibilidad del cliente')
                        ->content(fn (?Pendiente $record): string => $record?->client_availability ?: 'Sin franja informada')
                        ->visible(fn (): bool => Pendiente::hasColumn('client_availability')),
                    Placeholder::make('schedule_summary')
                        ->label('Fecha sugerida')
                        ->content(function (?Pendiente $record): string {
                            if (! $record?->due_date) {
                                return 'A programar';
                            }

                            return Carbon::parse($record->due_date)->format('d/m/Y');
                        }),
                    Placeholder::make('description_summary')
                        ->label('Detalle del caso')
                        ->content(fn (?Pendiente $record): string => $record?->description ?? '-')
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 3,
                ]),

            Section::make('Circuito rapido')
                ->description('Mismo flujo operativo en formato resumido para planificar, firmar y cerrar.')
                ->schema([
                    View::make('filament.pendientes.guide')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Planificacion de Alfredo')
                ->description('Revision, diagnostico inicial, repuestos posibles y definicion operativa.')
                ->schema([
                    Select::make('reviewed_by_user_id')
                        ->label('Revisado por')
                        ->relationship('reviewedBy', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('assigned_by_user_id')
                        ->label('Asignado por')
                        ->relationship('assignedBy', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('user_id')
                        ->label('Tecnico asignado')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('priority')
                        ->label('Prioridad operativa final')
                        ->options(Pendiente::getPriorityOptions())
                        ->required(),
                    TextInput::make('client_availability')
                        ->label('Disponibilidad del cliente')
                        ->maxLength(255)
                        ->placeholder('Ej: de 10 a 13 hs / solo por la tarde')
                        ->visible(fn (): bool => Pendiente::hasColumn('client_availability')),
                    TextInput::make('estimated_time')
                        ->label('Tiempo estimado')
                        ->maxLength(255)
                        ->placeholder('Ej: 2 hs / media jornada')
                        ->visible(fn (): bool => Pendiente::hasColumn('estimated_time')),
                    TextInput::make('neighborhood')
                        ->label('Barrio')
                        ->disabled()
                        ->dehydrated(false)
                        ->maxLength(255),
                    Select::make('operational_zone')
                        ->label('Zona operativa')
                        ->options(Pendiente::getOperationalZoneOptions())
                        ->disabled()
                        ->dehydrated(false)
                        ->searchable(),
                    TextInput::make('service_order_number')
                        ->label('Orden de trabajo')
                        ->maxLength(255)
                        ->placeholder('Ej: OT-2026-0154'),
                    DatePicker::make('assigned_at')
                        ->label('Fecha de asignacion')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->closeOnDateSelection(),
                    Textarea::make('review_notes')
                        ->label('Revision de circuito y criterio')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('initial_diagnosis')
                        ->label('Diagnostico inicial')
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(fn (): bool => Pendiente::hasColumn('initial_diagnosis')),
                    Textarea::make('possible_spare_parts')
                        ->label('Repuestos posibles')
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(fn (): bool => Pendiente::hasColumn('possible_spare_parts')),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ]),

            Section::make('Preparacion tecnica y firma previa')
                ->description('Herramientas e instrucciones que el tecnico valida antes de salir a la visita.')
                ->schema([
                    CheckboxList::make('required_tools')
                        ->label('Herramientas requeridas')
                        ->options(fn (callable $get): array => Pendiente::getToolOptionsForType($get('type')))
                        ->columns(2),
                    Placeholder::make('inventory_units_summary')
                        ->label('Equipos reservados')
                        ->content(function (?Pendiente $record): HtmlString {
                            if (! $record) {
                                return new HtmlString('Sin pendiente cargado.');
                            }

                            $assignments = $record->activeUnitAssignments()
                                ->with('unit.variante.producto')
                                ->get();

                            if ($assignments->isEmpty()) {
                                return new HtmlString('Sin equipos reservados.');
                            }

                            $lines = $assignments
                                ->map(function ($assignment): string {
                                    $unit = $assignment->unit;

                                    if (! $unit) {
                                        return 'Unidad no disponible en catalogo';
                                    }

                                    $productName = $unit->variante?->producto?->nombre;
                                    $model = $unit->variante?->modelo;

                                    return collect([
                                        $unit->codigo_barra,
                                        $productName,
                                        $model,
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
                    Select::make('inventory_unit_ids')
                        ->label('Equipos / unidades para esta orden')
                        ->options(fn (?Pendiente $record): array => $record
                            ? app(ProductoUnidadAssignmentService::class)->optionsForPendiente($record)
                            : [])
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Reservan equipos serializados para esta orden. Se liberan o resuelven automaticamente desde la visita.')
                        ->columnSpanFull()
                        ->dehydrated(false),
                    Textarea::make('work_order')
                        ->label('Orden de trabajo / instrucciones')
                        ->rows(4)
                        ->columnSpanFull(),
                    Checkbox::make('technician_acknowledged')
                        ->label('El tecnico confirma tarea y herramientas'),
                    TextInput::make('technician_signature_name')
                        ->label('Firma previa del tecnico')
                        ->maxLength(255),
                    DatePicker::make('technician_signature_at')
                        ->label('Fecha de firma previa')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->closeOnDateSelection(),
                    Textarea::make('technician_signature_notes')
                        ->label('Observaciones previas del tecnico')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ]),

            Section::make('Control y cierre')
                ->description('Estado final, firma posterior a la visita y resumen ejecutivo.')
                ->schema([
                    ToggleButtons::make('status')
                        ->label('Estado actual')
                        ->options(Pendiente::getStatusOptions())
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
                        ->required()
                        ->columnSpanFull(),
                    DatePicker::make('performed_at')
                        ->label('Fecha real de ejecucion')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->closeOnDateSelection(),
                    TextInput::make('remito')
                        ->label('N de remito')
                        ->maxLength(7)
                        ->regex('/^\d{2}-\d{4}$/')
                        ->validationMessages([
                            'regex' => 'Formato invalido. Usar: 00-0000',
                        ])
                        ->nullable(),
                    Checkbox::make('post_visit_acknowledged')
                        ->label('Conformidad final posterior a la visita')
                        ->visible(fn (): bool => Pendiente::hasColumn('post_visit_acknowledged')),
                    TextInput::make('post_visit_signature_name')
                        ->label('Firma final del tecnico')
                        ->maxLength(255)
                        ->visible(fn (): bool => Pendiente::hasColumn('post_visit_signature_name')),
                    DatePicker::make('post_visit_signature_at')
                        ->label('Fecha de firma final')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->visible(fn (): bool => Pendiente::hasColumn('post_visit_signature_at')),
                    Textarea::make('post_visit_signature_notes')
                        ->label('Observaciones finales del tecnico')
                        ->rows(4)
                        ->columnSpanFull()
                        ->visible(fn (): bool => Pendiente::hasColumn('post_visit_signature_notes')),
                    Textarea::make('control_summary')
                        ->label('Resumen de control')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Ultima novedad')
                        ->rows(4)
                        ->helperText('Se agrega automaticamente a la historia del pendiente.')
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ]),

            Section::make('Historia y seguimiento')
                ->schema([
                    View::make('filament.pendientes.history')
                        ->columnSpanFull(),
                ])
                ->visible(fn (?Pendiente $record): bool => filled($record))
                ->columnSpanFull(),
        ]);
    }
}
