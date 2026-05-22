<?php

namespace App\Filament\Resources\Conservations\Schemas;

use App\Models\Conservation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ConservationForm
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable()
                    ->columnSpanFull(),

                Placeholder::make('contract_snapshot')
                    ->label('Estado del contrato')
                    ->content(function (?Conservation $record): string {
                        if (! $record) {
                            return 'Al crear el contrato, el ciclo empieza en 01 y el estado queda activo.';
                        }

                        $cycle = str_pad((string) ($record->contract_cycle_number ?? 1), 2, '0', STR_PAD_LEFT);
                        $renewalDate = $record->renewal_required_at?->format('d/m/Y');

                        return "Ciclo {$cycle} · {$record->contract_status_label}"
                            . ($renewalDate ? " · Renovación requerida desde {$renewalDate}" : '');
                    })
                    ->columnSpanFull(),

                TextInput::make('current_service_number')
                    ->label('Próximo servicio del ciclo')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->helperText('Se actualiza automáticamente cada vez que registrás un mantenimiento o renovás el contrato.'),

                TextInput::make('total_services')
                    ->label('Total de servicios por ciclo')
                    ->numeric()
                    ->minValue(1)
                    ->default(12),

                DatePicker::make('start_date')
                    ->label('Inicio del contrato')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(false)
                    ->required(),

                DatePicker::make('expiration_date')
                    ->label('Vencimiento del contrato')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(false)
                    ->required(),

                DatePicker::make('next_service_date')
                    ->label('Próximo servicio')
                    ->displayFormat('d/m/Y')
                    ->format('Y-m-d')
                    ->native(false)
                    ->helperText('Se recalcula automáticamente después de cada mantenimiento y queda vacío cuando el contrato requiere renovación.'),

                Select::make('frequency')
                    ->label('Frecuencia de mantenimiento')
                    ->options([
                        'mensual' => 'Mensual',
                        'trimestral' => 'Trimestral',
                        'semestral' => 'Semestral',
                        'anual' => 'Anual',
                    ])
                    ->required(),

                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),

                View::make('filament.conservations.timeline')
                    ->columnSpanFull()
                    ->visible(fn ($record) => $record !== null),
            ]);
    }
}
