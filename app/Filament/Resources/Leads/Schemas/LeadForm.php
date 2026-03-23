<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;

class LeadForm
{
    public static function make(): array
    {
        return [
            Forms\Components\Section::make('Datos del Lead')
                ->schema([
                    Forms\Components\TextInput::make('crm_id')
                        ->disabled()
                        ->dehydrated(false)
                        ->label('ID CRM'),
                    Forms\Components\DatePicker::make('fecha_ingreso')
                        ->default(now())
                        ->displayFormat('d/m/Y')
                        ->label('Fecha Ingreso'),
                    Forms\Components\DatePicker::make('hora_ingreso')
                        ->default(now())
                        ->displayFormat('H:i')
                        ->label('Hora Ingreso'),
                    Forms\Components\Select::make('comercial_asignado_id')
                        ->relationship('comercialAsignado', 'name')
                        ->searchable()
                        ->preload()
                        ->label('Comercial Asignado'),
                    Forms\Components\Select::make('canal_origen')
                        ->options([
                            'whatsapp' => 'WhatsApp',
                            'redes_sociales' => 'Redes Sociales',
                            'mail' => 'Mail',
                            'telefono' => 'Teléfono',
                        ])
                        ->default('whatsapp')
                        ->label('Canal Origen'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Datos del Cliente')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('apellido')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('telefono')
                        ->tel()
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('localidad')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('provincia')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('zona_comercial')
                        ->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('Clasificación del Cliente')
                ->schema([
                    Forms\Components\Select::make('tipo_cliente')
                        ->options([
                            'Residencial' => 'Residencial',
                            'Público' => 'Público',
                            'Empresa' => 'Empresa',
                            'Constructor' => 'Constructor',
                        ]),
                    Forms\Components\Select::make('subtipo_publico')
                        ->options([
                            'Universidad' => 'Universidad',
                            'Municipalidad' => 'Municipalidad',
                            'Banco' => 'Banco',
                            'Hospital' => 'Hospital',
                            'Otro' => 'Otro',
                        ])
                        ->visible(fn (Get $get) => $get('tipo_cliente') === 'Público'),
                ]),

            Forms\Components\Section::make('Interés Inicial')
                ->schema([
                    Forms\Components\TextInput::make('producto_interes')
                        ->maxLength(255),
                    Forms\Components\Select::make('tipo_instalacion')
                        ->options([
                            'Recta' => 'Recta',
                            'Curva' => 'Curva',
                            'Exterior' => 'Exterior',
                            'Piscina' => 'Piscina',
                            'Vertical' => 'Vertical',
                        ]),
                    Forms\Components\Textarea::make('documentacion_cliente')
                        ->maxLength(65535)
                        ->rows(3),
    Forms\Components\CheckboxList::make('docs')
                        ->label('Documentación enviada')
                        ->options([
                            'cliente_envio_fotos' => 'Fotos',
                            'cliente_envio_planos' => 'Planos',
                            'requiere_relevamiento_pago' => 'Requiere relevamiento pago',
                        ])
                        ->columns(1),
                ]),

            Forms\Components\Section::make('Orientación de Costos')
                ->schema([
    Forms\Components\Toggle::make('orientacion_dada')
                        ->label('Orientación Dada'),
                    Forms\Components\Select::make('tipo_orientacion')
                        ->options([
                            'Verbal telefónica' => 'Verbal telefónica',
                            'Audio WhatsApp' => 'Audio WhatsApp',
                            'Texto WhatsApp' => 'Texto WhatsApp',
                            'Estimado estructurado WhatsApp' => 'Estimado estructurado WhatsApp',
                            'PDF enviado por WhatsApp' => 'PDF enviado por WhatsApp',
                            'PDF enviado por Mail' => 'PDF enviado por Mail',
                        ])
                        ->visible(fn (Get $get) => $get('orientacion_dada')),
                    Forms\Components\DatePicker::make('fecha_orientacion')
                        ->visible(fn (Get $get) => $get('orientacion_dada')),
                ]),

            Forms\Components\Section::make('Pipeline Comercial')
                ->schema([
                    Forms\Components\Select::make('estado_pipeline')
                        ->options([
                            'Ingresado' => 'Ingresado',
                            'Contactado' => 'Contactado',
                            'Orientacion dada' => 'Orientación dada',
                            'Cotizacion enviada' => 'Cotización enviada',
                            'Presupuesto definitivo enviado' => 'Presupuesto definitivo enviado',
                            'Venta cerrada' => 'Venta cerrada',
                            'Perdido' => 'Perdido',
                            'Postergado' => 'Postergado',
                        ])
                        ->default('Ingresado')
                        ->label('Estado Pipeline'),
                    Forms\Components\Select::make('resultado_final')
                        ->options([
                            'Abierto' => 'Abierto',
                            'Vendido' => 'Vendido',
                            'Perdido' => 'Perdido',
                        ]),
                    Forms\Components\Select::make('motivo_perdida')
                        ->options([
                            'Precio' => 'Precio',
                            'Forma de pago' => 'Forma de pago',
                            'Tiempo entrega' => 'Tiempo entrega',
                            'Competencia' => 'Competencia',
                            'Calidad percibida' => 'Calidad percibida',
                            'Falta decisión' => 'Falta decisión',
                            'Otro' => 'Otro',
                        ])
                        ->visible(fn (Get $get) => $get('resultado_final') === 'Perdido'),
                    Forms\Components\DatePicker::make('fecha_cierre'),
                    Forms\Components\Select::make('area_responsable')
                        ->options([
                            'Comercial Venta' => 'Comercial Venta',
                            'Postventa' => 'Postventa',
                        ]),
                ]),
        ];
    }
}

