<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Equipo;
use App\Models\Lead;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Illuminate\Support\HtmlString;

class LeadForm
{
    public static function make(): array
    {
        return [
            Section::make('Contexto comercial')
                ->description('Base operativa del lead dentro del CRM.')
                ->schema([
                    TextInput::make('crm_id')
                        ->label('ID CRM')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Se puede cargar manualmente. Si lo dejas vacio, se genera automaticamente al guardar.'),

                    DatePicker::make('fecha_ingreso')
                        ->label('Fecha de ingreso')
                        ->default(now())
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->required(),

                    TimePicker::make('hora_ingreso')
                        ->label('Hora de ingreso')
                        ->seconds(false)
                        ->default(now())
                        ->required(),

                    Select::make('comercial_asignado_id')
                        ->label('Comercial asignado')
                        ->relationship('comercialAsignado', 'name')
                        ->default(fn (): ?int => auth()->id())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('canal_origen')
                        ->label('Canal de origen')
                        ->options(Lead::getCanalOrigenOptions())
                        ->default('whatsapp')
                        ->required(),

                    Placeholder::make('seguimiento_health')
                        ->label('Salud del seguimiento')
                        ->content(function (?Lead $record): HtmlString {
                            if (! $record) {
                                return new HtmlString('Se calcula despues de guardar el lead.');
                            }

                            $label = match ($record->estado_semaforo) {
                                'urgente' => 'Urgente',
                                'atencion' => 'Atencion',
                                default => 'Al dia',
                            };

                            return new HtmlString(
                                '<span class="font-medium">' . e($label) . '</span> - ' .
                                e((string) $record->dias_sin_seguimiento) . ' dias sin gestion.'
                            );
                        }),
                ])
                ->columns(3),

            Section::make('Datos del cliente')
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('apellido')
                        ->label('Apellido')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('telefono')
                        ->label('Telefono')
                        ->tel()
                        ->required(),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('localidad')
                        ->label('Localidad')
                        ->maxLength(255),

                    TextInput::make('provincia')
                        ->label('Provincia')
                        ->maxLength(255),

                    TextInput::make('zona_comercial')
                        ->label('Zona comercial')
                        ->maxLength(255),

                    Select::make('tipo_cliente')
                        ->label('Tipo de cliente')
                        ->options(Lead::getTipoClienteOptions()),

                    Select::make('subtipo_publico')
                        ->label('Subtipo publico')
                        ->options(Lead::getSubtipoPublicoOptions())
                        ->visible(fn (Get $get): bool => $get('tipo_cliente') === 'Público'),
                ])
                ->columns(3),

            Section::make('Interes y calificacion')
                ->schema([
                    Select::make('producto_interes')
                        ->label('Producto de interes')
                        ->options(function (Get $get, ?Lead $record): array {
                            $options = Equipo::query()
                                ->orderBy('nombre')
                                ->pluck('nombre', 'nombre')
                                ->all();

                            $currentValue = $get('producto_interes') ?: $record?->producto_interes;

                            if (filled($currentValue) && ! array_key_exists($currentValue, $options)) {
                                $options[$currentValue] = $currentValue;
                            }

                            return $options;
                        })
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('tipo_instalacion')
                        ->label('Tipo de instalacion')
                        ->options(Lead::getTipoInstalacionOptions()),

                    FileUpload::make('documentacion_cliente_archivos')
                        ->label('Documentacion enviada por el cliente')
                        ->multiple()
                        ->disk('public')
                        ->directory('leads/documentacion')
                        ->maxFiles(10)
                        ->maxSize(51200)
                        ->downloadable()
                        ->openable()
                        ->helperText('Carga PDFs, imagenes o archivos de apoyo del lead. Hasta 10 archivos de 50 MB cada uno.')
                        ->columnSpanFull(),

                    Placeholder::make('documentacion_cliente_legacy')
                        ->label('Observacion historica')
                        ->content(fn (?Lead $record): string => trim((string) $record?->documentacion_cliente))
                        ->visible(fn (?Lead $record): bool => filled($record?->documentacion_cliente))
                        ->columnSpanFull(),

                    Toggle::make('cliente_envio_fotos')
                        ->label('Cliente envio fotos'),

                    Toggle::make('cliente_envio_planos')
                        ->label('Cliente envio planos'),

                    Toggle::make('requiere_relevamiento_pago')
                        ->label('Requiere relevamiento pago'),

                    Toggle::make('orientacion_dada')
                        ->label('Orientacion de costos realizada')
                        ->live(),

                    Select::make('tipo_orientacion')
                        ->label('Tipo de orientacion')
                        ->options(Lead::getTipoOrientacionOptions())
                        ->visible(fn (Get $get): bool => (bool) $get('orientacion_dada')),

                    DatePicker::make('fecha_orientacion')
                        ->label('Fecha de orientacion')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->visible(fn (Get $get): bool => (bool) $get('orientacion_dada')),
                ])
                ->columns(3),

            Section::make('Pipeline y cierre')
                ->description('Estado comercial, resultado y ownership del lead.')
                ->schema([
                    Select::make('estado_pipeline')
                        ->label('Estado del pipeline')
                        ->options(Lead::getPipelineOptions())
                        ->default(Lead::STAGE_INGRESADO)
                        ->required()
                        ->live(),

                    Select::make('resultado_final')
                        ->label('Resultado final')
                        ->options(Lead::getResultadoOptions())
                        ->default(Lead::RESULTADO_ABIERTO)
                        ->helperText('Se ajusta automaticamente cuando el lead se cierra como venta o perdido.'),

                    Select::make('motivo_perdida')
                        ->label('Motivo de perdida')
                        ->options(Lead::getMotivoPerdidaOptions())
                        ->visible(fn (Get $get): bool => $get('estado_pipeline') === Lead::STAGE_PERDIDO),

                    DatePicker::make('fecha_cierre')
                        ->label('Fecha de cierre')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->visible(fn (Get $get): bool => in_array($get('estado_pipeline'), Lead::CLOSED_PIPELINES, true)),

                    Select::make('area_responsable')
                        ->label('Area responsable')
                        ->options(Lead::getAreaResponsableOptions()),

                    Placeholder::make('last_follow_up')
                        ->label('Ultimo seguimiento')
                        ->content(fn (?Lead $record): string => $record?->fecha_ultimo_seguimiento?->format('d/m/Y') ?? 'Sin registros'),
                ])
                ->columns(3),

            Section::make('Trazabilidad')
                ->description('Linea de tiempo automatica del lead y sus movimientos comerciales.')
                ->schema([
                    View::make('filament.leads.history')
                        ->columnSpanFull(),
                ])
                ->visible(fn (?Lead $record): bool => filled($record))
                ->columnSpanFull(),
        ];
    }
}
