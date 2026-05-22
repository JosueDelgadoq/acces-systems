<?php

namespace App\Filament\Resources\CommercialGoals\Schemas;

use App\Models\CommercialGoal;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class CommercialGoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Objetivo mensual')
                ->description('Define metas por comercial y por mes para medir desvio real contra objetivo.')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('Comercial')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    DatePicker::make('goal_month')
                        ->label('Mes objetivo')
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->dehydrateStateUsing(fn ($state) => Carbon::parse($state)->startOfMonth()->toDateString())
                        ->rule(fn (Get $get, ?CommercialGoal $record) => Rule::unique('commercial_goals', 'goal_month')
                            ->ignore($record)
                            ->where(fn ($query) => $query->where('user_id', $get('user_id')))),

                    TextInput::make('target_leads')
                        ->label('Meta de leads')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('target_contacts')
                        ->label('Meta de contactos')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('target_quotes')
                        ->label('Meta de cotizaciones')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('target_sales')
                        ->label('Meta de ventas')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    TextInput::make('target_revenue')
                        ->label('Meta de facturacion')
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
