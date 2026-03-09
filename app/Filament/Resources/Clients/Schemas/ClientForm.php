<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),

                TextInput::make('company')
                    ->label('Empresa')
                    ->default(null),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->default(null),

                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->default(null),

                // Address fields
                TextInput::make('address')
                    ->label('Dirección')
                    ->default(null)
                    ->columnSpanFull(),

                TextInput::make('city')
                    ->label('Ciudad')
                    ->default(null),

                TextInput::make('state')
                    ->label('Provincia/Estado')
                    ->default(null),

                TextInput::make('postal_code')
                    ->label('Código Postal')
                    ->default(null),

                Textarea::make('address_notes')
                    ->label('Notas de dirección')
                    ->default(null)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Notas')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
