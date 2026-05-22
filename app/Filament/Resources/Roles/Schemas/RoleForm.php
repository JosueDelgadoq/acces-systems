<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Support\PermissionCatalog;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $permissionSections = [];

        foreach (PermissionCatalog::groupedOptionsFromDatabase() as $groupKey => $group) {
            $permissionSections[] = Section::make($group['label'])
                ->columnSpan(1)
                ->collapsible()
                ->compact()
                ->schema([
                    CheckboxList::make("permissions_{$groupKey}")
                        ->label('')
                        ->dehydrated(false)
                        ->options($group['permissions'])
                        ->columns(1)
                        ->bulkToggleable()
                        ->searchable(),
                ]);
        }

        return $schema->schema([
            Section::make('Datos del rol')
                ->description('Definicion del perfil y de su alcance operativo.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre del rol')
                        ->placeholder('Ej: Gerente, Tecnico, Administrativo')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),

            Section::make('Permisos del sistema')
                ->description('Cada modulo se muestra u oculta segun los permisos activos del rol.')
                ->columnSpanFull()
                ->columns([
                    'md' => 2,
                    'xl' => 3,
                ])
                ->schema($permissionSections),
        ]);
    }
}
