<?php

namespace App\Services\Samu;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Pendiente;
use App\Models\SamuEvent;
use Illuminate\Support\Str;

class SamuClassificationService
{
    public function classify(array $normalized, ?Lead $lead = null, ?Client $client = null): array
    {
        $text = $this->buildCorpus($normalized);

        $hasCommercialSignal = $this->hasCommercialSignals($normalized, $text);
        $hasOperationalSignal = $this->hasOperationalSignals($text);
        $hasClaimSignal = $this->hasClaimSignals($text);
        $hasMaintenanceSignal = $this->hasMaintenanceSignals($text);
        $hasDiagnosisSignal = $this->hasDiagnosisSignals($text);
        $hasInstallationSignal = $this->hasInstallationSignals($text);
        $hasDispatchSignal = $this->hasDispatchSignals($text);
        $hasHabilitationSignal = $this->hasHabilitationSignals($text);
        $hasExistingClient = filled($client);
        $hasExistingLead = filled($lead);

        if ($suggested = $this->resolveSuggestedClassification(
            $normalized['suggested_case_type'] ?? null,
            $normalized,
            $text,
            $hasExistingLead,
            $hasExistingClient,
        )) {
            return $suggested;
        }

        if ($this->hasLowSignalInteraction($normalized, $text)) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
                'reason' => 'La interaccion fue demasiado corta o no aporto informacion suficiente para automatizar acciones.',
                'should_create_client' => false,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => false,
                'pendiente_type' => null,
            ];
        }

        if (($normalized['is_habilitation'] ?? false) === true || $hasHabilitationSignal) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
                'reason' => 'Se detecto lenguaje de habilitacion, TAD, apoderamiento o seguimiento administrativo.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => true,
                'pendiente_type' => null,
            ];
        }

        if ($hasCommercialSignal && ($hasOperationalSignal || $hasClaimSignal) && ($hasExistingClient || $hasExistingLead)) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_MIXED,
                'reason' => 'Se detectaron senales comerciales y operativas sobre un contacto ya conocido.',
                'should_create_client' => true,
                'should_create_lead' => true,
                'should_write_lead_timeline' => true,
                'should_create_follow_up' => true,
                'should_create_claim' => $hasClaimSignal,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => $this->resolvePendienteType($text, $normalized),
            ];
        }

        if ($hasClaimSignal && ($hasExistingClient || ! $hasCommercialSignal)) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_CLAIM,
                'reason' => 'Se detecto reclamo, falla o lenguaje de postventa asociado a soporte tecnico.',
                'should_create_client' => true,
                'should_create_lead' => $hasExistingLead,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => true,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'reclamo',
            ];
        }

        if ($hasMaintenanceSignal) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_MAINTENANCE,
                'reason' => 'Se detecto mantenimiento, control periodico o servicio preventivo.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'mantenimiento',
            ];
        }

        if ($hasDiagnosisSignal) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_DIAGNOSIS,
                'reason' => 'Se detecto diagnostico, revision tecnica o visita para evaluar una falla.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'diagnostico',
            ];
        }

        if ($hasInstallationSignal && $hasExistingClient) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_INSTALLATION,
                'reason' => 'Se detecto instalacion o coordinacion operativa sobre un cliente ya existente.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'instalacion',
            ];
        }

        if ($hasDispatchSignal) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_DISPATCH,
                'reason' => 'Se detecto despacho, envio o coordinacion logistica.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'Despacho',
            ];
        }

        if ($hasOperationalSignal && $hasExistingClient) {
            return [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_SUPPORT,
                'reason' => 'Se detecto necesidad tecnica u operativa asociada a un cliente existente.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => $this->resolvePendienteType($text, $normalized),
            ];
        }

        if ($hasCommercialSignal || ! $hasExistingClient) {
            return [
                'classification' => $hasExistingLead
                    ? SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP
                    : SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
                'reason' => 'Se detectaron senales de interes comercial, pipeline o seguimiento de venta.',
                'should_create_client' => false,
                'should_create_lead' => true,
                'should_write_lead_timeline' => true,
                'should_create_follow_up' => true,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => false,
                'pendiente_type' => null,
            ];
        }

        return [
            'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'reason' => 'No hubo suficientes senales para decidir entre comercial y postventa. Se conserva para revision manual.',
            'should_create_client' => $hasExistingClient,
            'should_create_lead' => false,
            'should_write_lead_timeline' => false,
            'should_create_follow_up' => false,
            'should_create_claim' => false,
            'should_create_pendiente' => false,
            'should_create_habilitation' => false,
            'pendiente_type' => null,
        ];
    }

    protected function buildCorpus(array $normalized): string
    {
        $taskTitles = collect($normalized['tasks_detected'] ?? [])
            ->pluck('title')
            ->filter()
            ->implode(' ');

        return Str::of(collect([
            $normalized['event_type'] ?? null,
            $normalized['suggested_case_type'] ?? null,
            $normalized['pipeline_stage_raw'] ?? null,
            $normalized['summary'] ?? null,
            $normalized['transcript'] ?? null,
            $normalized['next_step'] ?? null,
            $normalized['habilitation_blocker'] ?? null,
            $normalized['habilitation_next_action'] ?? null,
            $normalized['task_type'] ?? null,
            implode(' ', $normalized['objections'] ?? []),
            $taskTitles,
        ])->filter()->implode(' '))
            ->lower()
            ->ascii()
            ->value();
    }

    protected function resolveSuggestedClassification(
        ?string $suggestedClassification,
        array $normalized,
        string $text,
        bool $hasExistingLead,
        bool $hasExistingClient,
    ): ?array {
        return match ($suggestedClassification) {
            SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD => [
                'classification' => SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
                'reason' => 'Samu sugirio catalogarlo como lead comercial.',
                'should_create_client' => false,
                'should_create_lead' => true,
                'should_write_lead_timeline' => true,
                'should_create_follow_up' => true,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => false,
                'pendiente_type' => null,
            ],
            SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP => [
                'classification' => SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP,
                'reason' => 'Samu sugirio catalogarlo como seguimiento comercial.',
                'should_create_client' => false,
                'should_create_lead' => true,
                'should_write_lead_timeline' => true,
                'should_create_follow_up' => true,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => false,
                'pendiente_type' => null,
            ],
            SamuEvent::CLASSIFICATION_POST_SALE_CLAIM => [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_CLAIM,
                'reason' => 'Samu sugirio catalogarlo como reclamo de postventa.',
                'should_create_client' => true,
                'should_create_lead' => $hasExistingLead,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => true,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'reclamo',
            ],
            SamuEvent::CLASSIFICATION_POST_SALE_SUPPORT => [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_SUPPORT,
                'reason' => 'Samu sugirio catalogarlo como soporte de postventa.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => $this->resolvePendienteType($text, $normalized),
            ],
            SamuEvent::CLASSIFICATION_POST_SALE_MAINTENANCE => [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_MAINTENANCE,
                'reason' => 'Samu sugirio catalogarlo como mantenimiento de postventa.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'mantenimiento',
            ],
            SamuEvent::CLASSIFICATION_POST_SALE_DIAGNOSIS => [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_DIAGNOSIS,
                'reason' => 'Samu sugirio catalogarlo como diagnostico tecnico.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'diagnostico',
            ],
            SamuEvent::CLASSIFICATION_POST_SALE_INSTALLATION => [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_INSTALLATION,
                'reason' => 'Samu sugirio catalogarlo como instalacion postventa.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'instalacion',
            ],
            SamuEvent::CLASSIFICATION_POST_SALE_DISPATCH => [
                'classification' => SamuEvent::CLASSIFICATION_POST_SALE_DISPATCH,
                'reason' => 'Samu sugirio catalogarlo como despacho o logistica.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => 'Despacho',
            ],
            SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP => [
                'classification' => SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
                'reason' => 'Samu sugirio catalogarlo como seguimiento de habilitacion.',
                'should_create_client' => true,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => true,
                'pendiente_type' => null,
            ],
            SamuEvent::CLASSIFICATION_MIXED => [
                'classification' => SamuEvent::CLASSIFICATION_MIXED,
                'reason' => 'Samu sugirio catalogarlo como mixto entre comercial y postventa.',
                'should_create_client' => true,
                'should_create_lead' => true,
                'should_write_lead_timeline' => true,
                'should_create_follow_up' => true,
                'should_create_claim' => $this->hasClaimSignals($text),
                'should_create_pendiente' => true,
                'should_create_habilitation' => false,
                'pendiente_type' => $this->resolvePendienteType($text, $normalized),
            ],
            SamuEvent::CLASSIFICATION_UNCLASSIFIED => [
                'classification' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
                'reason' => 'Samu no pudo clasificar el caso con suficiente confianza.',
                'should_create_client' => $hasExistingClient,
                'should_create_lead' => false,
                'should_write_lead_timeline' => false,
                'should_create_follow_up' => false,
                'should_create_claim' => false,
                'should_create_pendiente' => false,
                'should_create_habilitation' => false,
                'pendiente_type' => null,
            ],
            default => null,
        };
    }

    protected function hasCommercialSignals(array $normalized, string $text): bool
    {
        if (filled($normalized['mapped_pipeline_stage']) || filled($normalized['interest_level'])) {
            return true;
        }

        return Str::contains($text, [
            'presupuesto',
            'cotizacion',
            'cotizar',
            'venta',
            'comprar',
            'compra',
            'interesado',
            'interesada',
            'demo',
            'propuesta',
            'seguir contacto',
            'llamar manana',
            'contactar',
            'lead',
            'nuevo cliente',
        ]);
    }

    protected function hasOperationalSignals(string $text): bool
    {
        return Str::contains($text, [
            'visita tecnica',
            'servicio tecnico',
            'tecnico',
            'mantenimiento',
            'diagnostico',
            'reparacion',
            'soporte',
            'bateria',
            'repuesto',
            'despacho',
            'instalacion',
            'garantia',
            'falla',
            'no funciona',
            'dejo de funcionar',
            'postventa',
            'reclamo',
        ]);
    }

    protected function hasClaimSignals(string $text): bool
    {
        return Str::contains($text, [
            'reclamo',
            'queja',
            'falla',
            'no funciona',
            'dejo de funcionar',
            'mal funcionamiento',
            'garantia',
            'error tecnico',
            'desperfecto',
            'incidente',
        ]);
    }

    protected function hasMaintenanceSignals(string $text): bool
    {
        return Str::contains($text, [
            'mantenimiento',
            'preventivo',
            'service periodico',
            'control anual',
            'revision anual',
            'conservacion',
        ]);
    }

    protected function hasDiagnosisSignals(string $text): bool
    {
        return Str::contains($text, [
            'diagnostico',
            'revisar falla',
            'inspeccion tecnica',
            'evaluar problema',
        ]);
    }

    protected function hasInstallationSignals(string $text): bool
    {
        return Str::contains($text, [
            'instalacion',
            'instalar',
            'puesta en marcha',
            'coordinacion de obra',
        ]);
    }

    protected function hasDispatchSignals(string $text): bool
    {
        return Str::contains($text, [
            'despacho',
            'envio',
            'entrega',
            'logistica',
            'remito',
        ]);
    }

    protected function hasHabilitationSignals(string $text): bool
    {
        return Str::contains($text, [
            'habilitacion',
            'tad',
            'tramite',
            'apoderamiento',
            'apoderar',
            'ingeniero',
            'gestor',
            'presentacion',
            'documentacion',
            'expediente',
            'miriam',
            'maria debe hablar',
            'reiniciar el tramite',
        ]);
    }

    protected function hasLowSignalInteraction(array $normalized, string $text): bool
    {
        $hasActionableSignals = filled($normalized['interest_level'] ?? null)
            || filled($normalized['next_step'] ?? null)
            || filled($normalized['mapped_pipeline_stage'] ?? null)
            || filled($normalized['pipeline_stage_raw'] ?? null)
            || (($normalized['tasks_detected'] ?? []) !== []);

        if (($normalized['duration_minutes'] ?? null) !== null && (int) $normalized['duration_minutes'] <= 1 && ! $hasActionableSignals) {
            return true;
        }

        $lowSignalPhrases = array_merge(
            SamuEvent::LOW_SIGNAL_TEXT_MARKERS,
            [
                'sin informacion',
                'sin contenido suficiente',
                'no se pudo elaborar un analisis',
            ],
        );

        return Str::contains($text, $lowSignalPhrases);
    }

    protected function resolvePendienteType(string $text, array $normalized): string
    {
        return match (true) {
            ($normalized['task_type'] ?? null) === 'reclamo_postventa' => 'reclamo',
            ($normalized['task_type'] ?? null) === 'soporte_postventa' => 'soporte',
            ($normalized['task_type'] ?? null) === 'mantenimiento' => 'mantenimiento',
            ($normalized['task_type'] ?? null) === 'diagnostico' => 'diagnostico',
            ($normalized['task_type'] ?? null) === 'instalacion' => 'instalacion',
            ($normalized['task_type'] ?? null) === 'despacho' => 'Despacho',
            $this->hasClaimSignals($text) => 'reclamo',
            $this->hasMaintenanceSignals($text) => 'mantenimiento',
            $this->hasDiagnosisSignals($text) => 'diagnostico',
            Str::contains($text, ['reparacion', 'reparar']) => 'reparacion',
            Str::contains($text, ['soporte', 'bateria', 'repuesto']) => 'soporte',
            $this->hasInstallationSignals($text) => 'instalacion',
            $this->hasDispatchSignals($text) => 'Despacho',
            ($normalized['mapped_pipeline_stage'] ?? null) === Lead::STAGE_PRESUPUESTO => 'presupuesto',
            default => 'diagnostico',
        };
    }
}
