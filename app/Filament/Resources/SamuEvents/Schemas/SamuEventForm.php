<?php

namespace App\Filament\Resources\SamuEvents\Schemas;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Habilitations\HabilitationResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Pendientes\PendienteResource;
use App\Models\SamuEvent;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class SamuEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Recepcion')
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('id')
                        ->label('#')
                        ->disabled(),
                    TextInput::make('external_id')
                        ->label('External ID')
                        ->disabled(),
                    TextInput::make('source')
                        ->label('Origen')
                        ->disabled(),
                    TextInput::make('event_type')
                        ->label('Tipo de evento')
                        ->disabled(),
                    Toggle::make('processed')
                        ->label('Procesado')
                        ->disabled(),
                    TextInput::make('processed_at')
                        ->label('Procesado el')
                        ->disabled()
                        ->formatStateUsing(fn ($state): string => filled($state) ? Carbon::parse($state)->format('d/m/Y H:i') : 'Pendiente'),
                    TextInput::make('created_at')
                        ->label('Recibido el')
                        ->disabled()
                        ->formatStateUsing(fn ($state): string => filled($state) ? Carbon::parse($state)->format('d/m/Y H:i') : 'Sin fecha'),
                    TextInput::make('classification')
                        ->label('Catalogacion')
                        ->disabled()
                        ->formatStateUsing(fn (?string $state): string => SamuEvent::getClassificationLabel($state)),
                    TextInput::make('manual_classification')
                        ->label('Override manual')
                        ->disabled()
                        ->formatStateUsing(fn (?string $state): string => filled($state) ? SamuEvent::getClassificationLabel($state) : 'Automatico'),
                    TextInput::make('manual_reviewed_at')
                        ->label('Revision manual')
                        ->disabled()
                        ->formatStateUsing(fn ($state): string => filled($state) ? Carbon::parse($state)->format('d/m/Y H:i') : 'Sin revision'),
                    Placeholder::make('manual_reviewer')
                        ->label('Revisado por')
                        ->content(fn (?SamuEvent $record): string => $record?->manualReviewer?->name ?? 'Sin revisor'),
                    Placeholder::make('lead_link')
                        ->label('Lead relacionado')
                        ->content(function (?SamuEvent $record): HtmlString {
                            if (! $record?->lead_id) {
                                return new HtmlString('Sin lead vinculado');
                            }

                            $label = $record->lead?->crm_id
                                ? $record->lead->crm_id . ' - ' . trim(($record->lead->nombre ?? '') . ' ' . ($record->lead->apellido ?? ''))
                                : 'Abrir lead';

                            return new HtmlString('<a href="' . e(LeadResource::getUrl('edit', ['record' => $record->lead_id])) . '" class="text-primary-600 underline">' . e($label) . '</a>');
                        }),
                    Placeholder::make('client_link')
                        ->label('Cliente relacionado')
                        ->content(function (?SamuEvent $record): HtmlString {
                            if (! $record?->client_id) {
                                return new HtmlString('Sin cliente vinculado');
                            }

                            return new HtmlString('<a href="' . e(ClientResource::getUrl('edit', ['record' => $record->client_id])) . '" class="text-primary-600 underline">' . e($record->client?->name ?? ('Cliente #' . $record->client_id)) . '</a>');
                        }),
                    Placeholder::make('claim_link')
                        ->label('Claim relacionado')
                        ->content(function (?SamuEvent $record): HtmlString {
                            if (! $record?->claim_id) {
                                return new HtmlString('Sin claim vinculado');
                            }

                            return new HtmlString('<a href="' . e(ClaimResource::getUrl('edit', ['record' => $record->claim_id])) . '" class="text-primary-600 underline">' . e($record->claim?->title ?? ('Claim #' . $record->claim_id)) . '</a>');
                        }),
                    Placeholder::make('pendiente_link')
                        ->label('Pendiente relacionado')
                        ->content(function (?SamuEvent $record): HtmlString {
                            if (! $record?->pendiente_id) {
                                return new HtmlString('Sin pendiente vinculado');
                            }

                            return new HtmlString('<a href="' . e(PendienteResource::getUrl('edit', ['record' => $record->pendiente_id])) . '" class="text-primary-600 underline">Abrir pendiente #' . e((string) $record->pendiente_id) . '</a>');
                        }),
                    Placeholder::make('habilitation_link')
                        ->label('Habilitacion relacionada')
                        ->content(function (?SamuEvent $record): HtmlString {
                            if (! $record?->habilitation_id) {
                                return new HtmlString('Sin habilitacion vinculada');
                            }

                            $label = $record->habilitation?->equipment
                                ? $record->habilitation->equipment . ' (#' . $record->habilitation_id . ')'
                                : 'Abrir habilitacion #' . $record->habilitation_id;

                            return new HtmlString('<a href="' . e(HabilitationResource::getUrl('edit', ['record' => $record->habilitation_id])) . '" class="text-primary-600 underline">' . e($label) . '</a>');
                        }),
                ]),

            Section::make('Revision manual')
                ->schema([
                    Textarea::make('manual_review_notes')
                        ->label('Notas de revision')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ]),

            Section::make('Analisis comercial')
                ->columns([
                    'md' => 2,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('interest_level')
                        ->label('Nivel de interes')
                        ->disabled()
                        ->formatStateUsing(fn (?string $state): string => SamuEvent::getInterestLevelLabel($state)),
                    TextInput::make('pipeline_stage')
                        ->label('Pipeline sugerido')
                        ->disabled(),
                    Textarea::make('classification_reason')
                        ->label('Motivo de catalogacion')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                    Textarea::make('summary')
                        ->label('Resumen')
                        ->rows(4)
                        ->disabled()
                        ->columnSpanFull(),
                    Textarea::make('next_step')
                        ->label('Proximo paso')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                    Textarea::make('transcript')
                        ->label('Transcripcion')
                        ->rows(8)
                        ->disabled()
                        ->columnSpanFull(),
                    Textarea::make('error_message')
                        ->label('Ultimo error')
                        ->rows(4)
                        ->disabled()
                        ->columnSpanFull(),
                ]),

            Section::make('Payload y auditoria')
                ->schema([
                    View::make('filament.samu-events.detail')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
