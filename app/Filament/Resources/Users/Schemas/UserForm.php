<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de acceso')
                ->description('Usuario, credenciales y perfil operativo.')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->label('Nombre')
                        ->maxLength(255),

                    TextInput::make('email')
                        ->required()
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('password')
                        ->label('Contrasena')
                        ->password()
                        ->maxLength(255)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),

                    Select::make('roles')
                        ->label('Roles asignados')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->required()
                        ->helperText('Recomendado: asignar un rol base. Usa roles multiples solo cuando realmente lo necesites.')
                        ->columnSpanFull(),
                ])
                ->columns([
                    'md' => 2,
                ]),
        ]);
    }
}
