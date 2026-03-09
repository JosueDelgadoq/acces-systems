<?php

namespace App\Filament\Resources\Claims\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('client_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('nuevo'),
                TextInput::make('technician_id')
                    ->numeric()
                    ->default(null),
                DatePicker::make('scheduled_visit'),
                Textarea::make('resolution')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('closed_at'),
            ]);
    }
}
