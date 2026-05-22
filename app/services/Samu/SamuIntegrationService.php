<?php

namespace App\Services\Samu;

use App\Models\Claim;
use App\Models\Client;
use App\Models\Habilitation;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\Pendiente;
use App\Models\SamuEvent;
use App\Models\Seguimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SamuIntegrationService
{
    protected const TIMELINE_EVENT_KEY = 'samu_event_received';

    public function __construct(
        protected SamuClassificationService $classifier,
    ) {
    }

    public function process(SamuEvent $event): SamuEvent
    {
        $normalized = $this->normalizePayload($event->payload ?? []);
        $normalized['suggested_case_type'] = $event->manual_classification ?: $normalized['suggested_case_type'];

        return DB::transaction(function () use ($event, $normalized): SamuEvent {
            $existingClient = $this->findClient($normalized);
            $existingLead = $this->findLead($normalized, $event);
            $classification = $this->classifier->classify($normalized, $existingLead, $existingClient);

            if (filled($event->manual_classification)) {
                $classification['reason'] = $this->buildManualClassificationReason($event, $classification['classification']);
            }

            $client = $existingClient;

            if (! $client && $classification['should_create_client']) {
                $client = $this->createClientFromPayload($normalized, $classification['classification']);
            }

            $lead = $existingLead;

            if (! $lead && $classification['should_create_lead']) {
                $lead = $this->createLeadFromPayload($normalized, $client, $classification['classification']);
            }

            if (! $classification['should_create_lead']) {
                $lead = null;
            }

            $claim = null;
            $pendiente = null;
            $habilitation = null;

            if ($classification['should_create_habilitation'] && $client) {
                $habilitation = $this->syncHabilitationFromPayload(
                    client: $client,
                    event: $event,
                    normalized: $normalized,
                    existing: $this->findHabilitation($client, $event, $normalized),
                );
            }

            if ($classification['should_create_claim'] && $client) {
                $claim = $this->createClaimFromPayload($client, $event, $normalized);
            }

            if ($classification['should_create_pendiente'] && $client) {
                $pendiente = $this->createPendienteTask(
                    client: $client,
                    lead: $lead,
                    event: $event,
                    normalized: $normalized,
                    pendingType: $classification['pendiente_type'],
                    claim: $claim,
                );
            }

            $event->forceFill([
                'external_id' => $normalized['external_id'],
                'event_type' => $normalized['event_type'],
                'lead_id' => $lead?->id,
                'client_id' => $client?->id,
                'claim_id' => $claim?->id,
                'pendiente_id' => $pendiente?->id,
                'habilitation_id' => $habilitation?->id,
                'summary' => $normalized['summary'],
                'transcript' => $normalized['transcript'],
                'interest_level' => $normalized['interest_level'],
            'next_step' => $normalized['next_step'],
            'pipeline_stage' => $this->resolveStoredPipelineStage($normalized, $classification),
            'classification' => $classification['classification'],
                'classification_reason' => $classification['reason'],
                'objections' => $normalized['objections'],
                'tasks_detected' => $normalized['tasks_detected'],
            ])->save();

            if ($lead && $classification['should_create_lead']) {
                $this->updatePipelineStage($lead, $normalized, $event);
            }

            if ($lead && $classification['should_write_lead_timeline']) {
                $this->createTimelineNote($lead, $event, $normalized, $classification);
            }

            $this->appendClientNote($client, $event, $normalized, $classification);

            if ($lead && $classification['should_create_follow_up']) {
                $this->createLeadFollowUpTask($lead, $event, $normalized, $classification);
            }

            Log::info('Samu event processed.', [
                'samu_event_id' => $event->id,
                'classification' => $classification['classification'],
                'lead_id' => $lead?->id,
                'client_id' => $client?->id,
                'claim_id' => $claim?->id,
                'pendiente_id' => $pendiente?->id,
                'habilitation_id' => $habilitation?->id,
                'external_id' => $event->external_id,
            ]);

            return $event->fresh(['lead', 'client', 'claim', 'pendiente', 'habilitation.client']);
        });
    }

    protected function normalizePayload(array $payload): array
    {
        $payload = $this->normalizeAssociativeArray($payload);
        $extractor = $this->normalizeAssociativeArray($payload['extractor'] ?? []);
        $sources = [$payload, $extractor];

        $summary = $this->normalizeText($this->extractFromSources($sources, [
            'summary',
            'resumen_operativo',
        ]));
        $transcript = $this->normalizeText($this->extractFromSources($sources, [
            'transcript',
            'conversation',
            'full_transcript',
            'transcripcion',
        ]));
        $nextStep = $this->normalizeText($this->extractFromSources($sources, [
            'next_step',
            'siguiente_paso_erp',
            'proximo_paso_claro',
            'proximo_paso_acordado',
            'siguiente_paso_acordado',
            'proxima_gestion_habilitacion',
        ]));
        $taskPriorityHint = $this->extractFromSources($sources, [
            'prioridad_sugerida',
            'prioridad_erp',
            'priority',
        ]);
        $explicitTaskTitle = $this->normalizeText($this->extractFromSources($sources, [
            'titulo_de_tarea_sugerida',
            'titulo_tarea_erp',
        ]));
        $requiresInternalTask = $this->normalizeBooleanFlag($this->extractFromSources($sources, [
            'requiere_tarea_interna',
            'crear_tarea_erp',
        ])) === true;
        $explicitFollowUpDate = $this->normalizeDateValue($this->extractFromSources($sources, [
            'fecha_compromiso_mencionada',
            'fecha_compromiso_erp',
        ]));

        $tasks = collect($payload['tasks_detected'] ?? [])
            ->filter(fn ($task) => is_array($task))
            ->map(function (array $task): array {
                return [
                    'title' => trim((string) ($task['title'] ?? '')),
                    'due_date' => filled($task['due_date'] ?? null)
                        ? $this->normalizeDateValue($task['due_date'])
                        : null,
                    'priority' => $this->normalizePriority($task['priority'] ?? null),
                ];
            })
            ->filter(fn (array $task): bool => filled($task['title']))
            ->values()
            ->all();

        $actionItems = $this->normalizeStringList($payload['actionItems'] ?? []);

        if ($tasks === [] && $actionItems !== []) {
            $tasks = collect($actionItems)
                ->map(fn (string $title): array => [
                    'title' => Str::limit($title, 255, ''),
                    'due_date' => $explicitFollowUpDate,
                    'priority' => $this->normalizePriority($taskPriorityHint) ?? 'media',
                ])
                ->values()
                ->all();
        }

        if ($tasks === [] && $requiresInternalTask && filled($explicitTaskTitle)) {
            $tasks = [[
                'title' => $explicitTaskTitle,
                'due_date' => $explicitFollowUpDate,
                'priority' => $this->normalizePriority($taskPriorityHint) ?? 'media',
            ]];
        }

        if (blank($nextStep) && $tasks !== []) {
            $nextStep = $tasks[0]['title'] ?? null;
        }

        $objections = collect([
            ...$this->normalizeStringList($this->extractFromSources($sources, ['objections', 'objeciones_detectadas'])),
            ...$this->normalizeStringList($this->extractFromSources($sources, ['razones_para_retraso_o_duda', 'bloqueante_habilitacion'])),
        ])
            ->unique()
            ->values()
            ->all();

        $pipelineStageRaw = filled($payload['pipeline_stage'] ?? null)
            ? trim((string) $payload['pipeline_stage'])
            : $this->normalizeText($this->extractFromSources($sources, [
                'pipeline_stage_suggested',
                'pipeline_stage',
                'etapa_pipeline_erp',
                'estado_pipeline',
                'estado_del_cliente_respecto_a_la_propuesta',
            ]));

        if (blank($summary) && filled($transcript)) {
            $summary = Str::limit($transcript, 240);
        }

        return [
            'external_id' => $this->normalizeText($this->extractFromSources($sources, ['external_id', 'id'])),
            'event_type' => $this->normalizeText($this->extractFromSources($sources, ['event_type', 'callTypeName', 'type'])) ?: 'commercial_interaction',
            'client_name' => $this->resolveClientNameFromPayload($payload, $extractor),
            'email' => $this->normalizeEmail($this->extractFromSources($sources, ['email', 'client_email', 'customer_email'])),
            'phone' => $this->resolvePhoneFromPayload($payload, $extractor),
            'summary' => $summary,
            'transcript' => $transcript,
            'interest_level' => $this->resolveInterestLevelFromPayload($payload, $extractor, $summary),
            'next_step' => $nextStep,
            'pipeline_stage_raw' => $pipelineStageRaw,
            'mapped_pipeline_stage' => $this->mapPipelineStage($pipelineStageRaw),
            'objections' => $objections,
            'tasks_detected' => $tasks,
            'priority' => $this->resolvePriority(
                interestLevel: $this->extractFromSources($sources, [
                    'interest_level',
                    'interes_erp',
                    'nivel_de_interes',
                    'interes_inicial_del_lead',
                ]),
                tasks: $tasks,
                priorityHint: $taskPriorityHint,
            ),
            'due_date' => $this->resolveDueDate($tasks),
            'suggested_case_type' => $this->normalizeSuggestedCaseType($this->extractFromSources($sources, [
                'tipo_de_caso_erp',
                'circuito_erp',
                'suggested_record_type',
            ])),
            'is_habilitation' => $this->normalizeBooleanFlag($this->extractFromSources($sources, [
                'es_habilitacion',
                'es_habilitacion_erp',
            ])) === true,
            'habilitation_status' => $this->normalizeHabilitationStatus($this->extractFromSources($sources, [
                'estado_habilitacion_sugerido',
                'estado_habilitacion_erp',
            ])),
            'habilitation_blocker' => $this->normalizeText($this->extractFromSources($sources, [
                'bloqueante_habilitacion',
                'bloqueante_habilitacion_erp',
                'razones_para_retraso_o_duda',
            ])),
            'habilitation_next_action' => $this->normalizeText($this->extractFromSources($sources, [
                'proxima_gestion_habilitacion',
                'proxima_gestion_habilitacion_erp',
                'proximo_paso_acordado',
                'siguiente_paso_acordado',
            ])),
            'next_step_owner' => $this->normalizeText($this->extractFromSources($sources, [
                'responsable_del_proximo_paso',
                'responsable_siguiente_paso_erp',
            ])),
            'explicit_follow_up_date' => $explicitFollowUpDate,
            'task_type' => $this->normalizeText($this->extractFromSources($sources, [
                'tipo_de_tarea_interna',
                'tipo_tarea_erp',
            ])),
            'duration_minutes' => $this->normalizeDurationMinutes($payload['duration'] ?? null),
            'call_type_name' => $this->normalizeText($payload['callTypeName'] ?? null),
            'host_email' => $this->normalizeEmail($payload['hostEmail'] ?? null),
            'probability_description' => $this->normalizeText($payload['probDesc'] ?? null),
        ];
    }

    protected function findLead(array $normalized, SamuEvent $event): ?Lead
    {
        if (filled($normalized['external_id'])) {
            $leadId = SamuEvent::query()
                ->where('external_id', $normalized['external_id'])
                ->whereNotNull('lead_id')
                ->where('id', '!=', $event->id)
                ->latest('id')
                ->value('lead_id');

            if ($leadId) {
                return Lead::query()->find($leadId);
            }
        }

        if (filled($normalized['email'])) {
            $lead = Lead::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($normalized['email'])])
                ->first();

            if ($lead) {
                return $lead;
            }
        }

        if (filled($normalized['phone'])) {
            return Lead::query()
                ->get()
                ->first(fn (Lead $lead): bool => $this->normalizePhone($lead->telefono) === $normalized['phone']);
        }

        return null;
    }

    protected function findClient(array $normalized): ?Client
    {
        if (filled($normalized['email'])) {
            $client = Client::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($normalized['email'])])
                ->first();

            if ($client) {
                return $client;
            }
        }

        if (filled($normalized['phone'])) {
            $client = Client::query()
                ->get()
                ->first(fn (Client $client): bool => $this->normalizePhone($client->phone) === $normalized['phone']);

            if ($client) {
                return $client;
            }
        }

        if (filled($normalized['client_name'])) {
            return Client::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($normalized['client_name'])])
                ->first();
        }

        return null;
    }

    protected function createLeadFromPayload(array $normalized, ?Client $client, string $classification): Lead
    {
        $owner = $this->resolveCommercialOwner();

        [$firstName, $lastName] = $this->splitClientName($normalized['client_name']);

        $lead = Lead::query()->create([
            'comercial_asignado_id' => $owner->id,
            'created_by' => $owner->id,
            'canal_origen' => $this->resolveLeadChannel($normalized),
            'nombre' => $firstName,
            'apellido' => $lastName,
            'telefono' => $normalized['phone'] ?? 'Sin telefono',
            'email' => $normalized['email'],
            'localidad' => $client?->city,
            'provincia' => $client?->state,
            'tipo_cliente' => 'Residencial',
            'producto_interes' => $this->extractProductInterest($normalized),
            'documentacion_cliente' => 'Lead creado automaticamente desde Samu.ai. Clasificacion: ' . SamuEvent::getClassificationLabel($classification) . '.',
            'orientacion_dada' => false,
            'estado_pipeline' => $normalized['mapped_pipeline_stage'] ?? Lead::STAGE_INGRESADO,
            'resultado_final' => Lead::RESULTADO_ABIERTO,
            'area_responsable' => 'Comercial Venta',
            'fecha_ultimo_seguimiento' => now()->toDateString(),
        ]);

        return $lead->fresh();
    }

    protected function createClientFromPayload(array $normalized, string $classification): ?Client
    {
        if (blank($normalized['client_name']) && blank($normalized['email']) && blank($normalized['phone'])) {
            return null;
        }

        return Client::query()->create([
            'name' => $normalized['client_name'] ?: 'Contacto Samu.ai',
            'email' => $normalized['email'],
            'phone' => $normalized['phone'],
            'notes' => 'Cliente generado automaticamente desde Samu.ai. Clasificacion: ' . SamuEvent::getClassificationLabel($classification) . '.',
        ]);
    }

    protected function createClaimFromPayload(Client $client, SamuEvent $event, array $normalized): ?Claim
    {
        $marker = '[SAMU_EVENT_ID:' . $event->id . ']';

        $existing = Claim::query()
            ->where('client_id', $client->id)
            ->where('description', 'like', '%' . $marker . '%')
            ->first();

        if ($existing) {
            return $existing;
        }

        $title = Str::limit(
            $normalized['summary']
                ?: ($normalized['tasks_detected'][0]['title'] ?? 'Reclamo detectado por Samu.ai'),
            255,
            ''
        );

        return Claim::query()->create([
            'client_id' => $client->id,
            'title' => $title,
            'description' => $this->buildClaimDescription($event, $normalized, $marker),
            'status' => 'nuevo',
            'scheduled_visit' => $normalized['due_date'],
        ]);
    }

    protected function findHabilitation(Client $client, SamuEvent $event, array $normalized): ?Habilitation
    {
        if ($event->habilitation_id) {
            $habilitation = Habilitation::query()->find($event->habilitation_id);

            if ($habilitation) {
                return $habilitation;
            }
        }

        if (filled($normalized['external_id'])) {
            $habilitationId = SamuEvent::query()
                ->where('external_id', $normalized['external_id'])
                ->whereNotNull('habilitation_id')
                ->where('id', '!=', $event->id)
                ->latest('id')
                ->value('habilitation_id');

            if ($habilitationId) {
                $habilitation = Habilitation::query()->find($habilitationId);

                if ($habilitation) {
                    return $habilitation;
                }
            }
        }

        return Habilitation::query()
            ->where('client_id', $client->id)
            ->whereIn('status', [
                'pendiente',
                'documentacion',
                'enviado_gestor',
                'en_tramite',
            ])
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    protected function syncHabilitationFromPayload(
        Client $client,
        SamuEvent $event,
        array $normalized,
        ?Habilitation $existing = null,
    ): Habilitation {
        $marker = '[SAMU_EVENT_ID:' . $event->id . ']';
        $status = $this->resolveHabilitationStatus($normalized, $existing);
        $equipment = $this->resolveHabilitationEquipment($normalized, $existing);
        $nextManagementDate = $this->resolveHabilitationNextManagementDate($normalized);
        $documentationComplete = match ($status) {
            'documentacion' => false,
            'enviado_gestor', 'en_tramite', 'aprobado' => true,
            default => null,
        };

        if (! $existing) {
            return Habilitation::query()->create([
                'client_id' => $client->id,
                'equipment' => $equipment,
                'status' => $status,
                'doc_completa' => $documentationComplete ?? false,
                'proxima_gestion' => $nextManagementDate,
                'observaciones' => $this->buildHabilitationObservations($event, $normalized, $marker),
            ])->fresh('client');
        }

        $updates = [];

        if ($this->shouldUpdateHabilitationEquipment($existing->equipment, $equipment)) {
            $updates['equipment'] = $equipment;
        }

        if ($this->shouldAdvanceHabilitationStatus($existing->status, $status)) {
            $updates['status'] = $status;
        }

        if ($nextManagementDate && optional($existing->proxima_gestion)->toDateString() !== $nextManagementDate) {
            $updates['proxima_gestion'] = $nextManagementDate;
        }

        if ($documentationComplete === true && ! $existing->doc_completa) {
            $updates['doc_completa'] = true;
        }

        if ($documentationComplete === false && $existing->status === 'pendiente' && $existing->doc_completa) {
            $updates['doc_completa'] = false;
        }

        if (! str_contains((string) $existing->observaciones, $marker)) {
            $updates['observaciones'] = trim(collect([
                trim((string) $existing->observaciones),
                $this->buildHabilitationObservations($event, $normalized, $marker),
            ])->filter()->implode(PHP_EOL . PHP_EOL));
        }

        if ($updates !== []) {
            $existing->forceFill($updates)->save();
        }

        return $existing->fresh('client');
    }

    protected function createTimelineNote(Lead $lead, SamuEvent $event, array $normalized, array $classification): void
    {
        $existing = LeadHistory::query()
            ->where('lead_id', $lead->id)
            ->where('event_key', self::TIMELINE_EVENT_KEY)
            ->where('meta->samu_event_id', $event->id)
            ->exists();

        if ($existing) {
            return;
        }

        $parts = collect([
            $normalized['summary'] ? 'Resumen: ' . $normalized['summary'] : null,
            $normalized['interest_level'] ? 'Interes detectado: ' . SamuEvent::getInterestLevelLabel($normalized['interest_level']) : null,
            $normalized['next_step'] ? 'Proximo paso sugerido: ' . $normalized['next_step'] : null,
            $normalized['mapped_pipeline_stage'] ? 'Pipeline sugerido: ' . Lead::getPipelineLabel($normalized['mapped_pipeline_stage']) : null,
            $normalized['objections'] !== [] ? 'Objeciones: ' . implode(', ', $normalized['objections']) : null,
            'Clasificacion: ' . SamuEvent::getClassificationLabel($classification['classification']),
        ])->filter()->implode('. ');

        LeadHistory::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $lead->comercial_asignado_id ?: $lead->created_by,
            'event_key' => self::TIMELINE_EVENT_KEY,
            'title' => 'Evento recibido desde Samu.ai',
            'description' => $parts !== '' ? $parts . '.' : 'Evento comercial recibido desde Samu.ai.',
            'status_from' => $lead->estado_pipeline,
            'status_to' => $lead->estado_pipeline,
            'meta' => [
                'samu_event_id' => $event->id,
                'external_id' => $normalized['external_id'],
                'interest_level' => $normalized['interest_level'],
                'classification' => $classification['classification'],
                'tasks_detected' => $normalized['tasks_detected'],
                'objections' => $normalized['objections'],
            ],
            'changed_at' => now(),
        ]);
    }

    protected function appendClientNote(?Client $client, SamuEvent $event, array $normalized, array $classification): void
    {
        if (! $client) {
            return;
        }

        $marker = '[SAMU_EVENT_ID:' . $event->id . ']';

        if (filled($client->notes) && str_contains((string) $client->notes, $marker)) {
            return;
        }

        $noteParts = collect([
            now()->format('d/m/Y H:i'),
            'Samu.ai',
            'Clasificacion: ' . SamuEvent::getClassificationLabel($classification['classification']),
            $normalized['summary'],
            $normalized['next_step'] ? 'Proximo paso: ' . $normalized['next_step'] : null,
            $normalized['objections'] !== [] ? 'Objeciones: ' . implode(', ', $normalized['objections']) : null,
            $marker,
        ])->filter()->implode(' | ');

        $client->forceFill([
            'notes' => trim(collect([
                trim((string) $client->notes),
                $noteParts,
            ])->filter()->implode(PHP_EOL . PHP_EOL)),
        ])->save();
    }

    protected function createLeadFollowUpTask(Lead $lead, SamuEvent $event, array $normalized, array $classification): void
    {
        $marker = '[SAMU_EVENT_ID:' . $event->id . ']';
        $nextAction = $normalized['next_step']
            ?: ($normalized['tasks_detected'][0]['title'] ?? null)
            ?: 'Revisar interaccion recibida desde Samu.ai';

        $existing = Seguimiento::query()
            ->where('lead_id', $lead->id)
            ->where('estado', Seguimiento::STATUS_PENDIENTE)
            ->where('observaciones', 'like', '%' . $marker . '%')
            ->exists();

        if ($existing) {
            return;
        }

        Seguimiento::query()->create([
            'lead_id' => $lead->id,
            'fecha_contacto' => null,
            'comercial_id' => $lead->comercial_asignado_id ?: $lead->created_by,
            'medio_contacto' => $this->resolveSeguimientoMedium($lead->canal_origen),
            'resultado' => null,
            'proxima_accion' => Str::limit($nextAction, 65535, ''),
            'fecha_proxima_accion' => $normalized['due_date'],
            'estado' => Seguimiento::STATUS_PENDIENTE,
            'observaciones' => $this->buildSeguimientoNotes($event, $normalized, $classification, $marker),
        ]);
    }

    protected function createPendienteTask(
        Client $client,
        ?Lead $lead,
        SamuEvent $event,
        array $normalized,
        ?string $pendingType,
        ?Claim $claim = null,
    ): ?Pendiente {
        $marker = '[SAMU_EVENT_ID:' . $event->id . ']';

        $existing = Pendiente::query()
            ->where('client_id', $client->id)
            ->where('status', Pendiente::STATUS_PENDING)
            ->where('notes', 'like', '%' . $marker . '%')
            ->first();

        if ($existing) {
            return $existing;
        }

        $owner = $this->resolvePostSaleOwner();
        $taskTitle = $normalized['tasks_detected'][0]['title'] ?? null;

        return Pendiente::query()->create([
            ...[
                'client_id' => $client->id,
                'user_id' => $owner?->id,
                'type' => $pendingType ?: 'diagnostico',
                'description' => Str::limit($taskTitle ?: ($normalized['summary'] ?: 'Pendiente generado desde Samu.ai'), 65535, ''),
                'due_date' => $normalized['due_date'] ?? now()->addDay()->toDateString(),
                'status' => Pendiente::STATUS_PENDING,
                'priority' => $normalized['priority'],
                'notes' => $this->buildPendienteNotes($event, $normalized, $claim, $marker),
            ],
            ...(Pendiente::hasColumn('review_notes') ? [
                'review_notes' => 'Pendiente generado automaticamente desde Samu.ai.',
            ] : []),
            ...(Pendiente::hasColumn('source') ? [
                'source' => 'samu',
            ] : []),
        ]);
    }

    protected function updatePipelineStage(Lead $lead, array $normalized, SamuEvent $event): void
    {
        $suggestedStage = $normalized['mapped_pipeline_stage'];

        if (blank($suggestedStage)) {
            return;
        }

        if (in_array($lead->estado_pipeline, Lead::CLOSED_PIPELINES, true)) {
            return;
        }

        if (! $this->shouldAdvancePipeline($lead->estado_pipeline, $suggestedStage)) {
            return;
        }

        $lead->forceFill([
            'estado_pipeline' => $suggestedStage,
        ])->save();

        Log::info('Samu advanced lead pipeline.', [
            'samu_event_id' => $event->id,
            'lead_id' => $lead->id,
            'pipeline_stage' => $suggestedStage,
        ]);
    }

    protected function shouldAdvancePipeline(?string $currentStage, ?string $suggestedStage): bool
    {
        if (blank($suggestedStage) || $currentStage === $suggestedStage) {
            return false;
        }

        $order = [
            Lead::STAGE_INGRESADO => 1,
            Lead::STAGE_CONTACTADO => 2,
            Lead::STAGE_ORIENTACION => 3,
            Lead::STAGE_COTIZACION => 4,
            Lead::STAGE_PRESUPUESTO => 5,
            Lead::STAGE_VENTA_CERRADA => 6,
            Lead::STAGE_PERDIDO => 6,
            Lead::STAGE_POSTERGADO => 2,
        ];

        return ($order[$suggestedStage] ?? 0) >= ($order[$currentStage] ?? 0);
    }

    protected function resolveCommercialOwner(): User
    {
        $owner = User::role('comercial')->orderBy('id')->first()
            ?? User::query()->role('admin')->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();

        if (! $owner) {
            throw new RuntimeException('No hay usuarios disponibles para asignar leads creados por Samu.ai.');
        }

        return $owner;
    }

    protected function resolvePostSaleOwner(): ?User
    {
        return User::role('Post Venta')->orderBy('id')->first()
            ?? User::role('Jefe Post-Venta')->orderBy('id')->first()
            ?? User::role('admin')->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();
    }

    protected function splitClientName(?string $clientName): array
    {
        $clean = trim((string) $clientName);

        if ($clean === '') {
            return ['Contacto', 'Samu'];
        }

        $parts = preg_split('/\s+/', $clean) ?: [];

        if (count($parts) === 1) {
            return [$parts[0], 'Samu'];
        }

        $lastName = array_pop($parts);

        return [implode(' ', $parts), $lastName ?: 'Samu'];
    }

    protected function resolveLeadChannel(array $normalized): string
    {
        if (filled($normalized['email'])) {
            return 'mail';
        }

        if (filled($normalized['phone'])) {
            return 'telefono';
        }

        return 'whatsapp';
    }

    protected function extractProductInterest(array $normalized): ?string
    {
        return $normalized['summary']
            ? Str::limit($normalized['summary'], 255, '')
            : null;
    }

    protected function resolvePriority(mixed $interestLevel, array $tasks, mixed $priorityHint = null): string
    {
        $normalizedPriorityHint = $this->normalizePriority($priorityHint);

        if ($normalizedPriorityHint) {
            return $normalizedPriorityHint;
        }

        foreach ($tasks as $task) {
            if (($task['priority'] ?? null) === 'alta') {
                return 'alta';
            }
        }

        return match ($this->normalizeInterestLevel($interestLevel)) {
            'alto' => 'alta',
            'bajo' => 'baja',
            default => 'media',
        };
    }

    protected function resolveDueDate(array $tasks): string
    {
        $firstDueDate = collect($tasks)
            ->pluck('due_date')
            ->filter()
            ->sort()
            ->first();

        return $firstDueDate ?: now()->addDay()->toDateString();
    }

    protected function mapPipelineStage(?string $value): ?string
    {
        $normalized = Str::of((string) $value)->trim()->lower()->ascii()->value();

        return match ($normalized) {
            '', null => null,
            'ingresado', 'nuevo', 'new', 'new_lead' => Lead::STAGE_INGRESADO,
            'contactado', 'contacted', 'contact' => Lead::STAGE_CONTACTADO,
            'orientacion', 'orientacion dada', 'orientation', 'guided' => Lead::STAGE_ORIENTACION,
            'cotizacion', 'cotizacion enviada', 'quote', 'quoted' => Lead::STAGE_COTIZACION,
            'presupuesto', 'budget', 'proposal', 'presupuesto definitivo enviado' => Lead::STAGE_PRESUPUESTO,
            'venta', 'venta cerrada', 'won', 'closed_won' => Lead::STAGE_VENTA_CERRADA,
            'perdido', 'lost', 'closed_lost' => Lead::STAGE_PERDIDO,
            'postergado', 'deferred', 'follow_later', 'pendiente', 'pending' => Lead::STAGE_POSTERGADO,
            default => null,
        };
    }

    protected function normalizeInterestLevel(mixed $value): ?string
    {
        $normalized = Str::of((string) $value)->trim()->lower()->ascii()->value();

        return match ($normalized) {
            '', null => null,
            'alto', 'alta', 'high', 'hot' => 'alto',
            'medio', 'media', 'medium', 'warm' => 'medio',
            'bajo', 'baja', 'low', 'cold' => 'bajo',
            default => $normalized,
        };
    }

    protected function normalizePriority(mixed $value): ?string
    {
        $normalized = Str::of((string) $value)->trim()->lower()->ascii()->value();

        return match ($normalized) {
            '', null => null,
            'alta', 'alto', 'high', 'urgent' => 'alta',
            'baja', 'bajo', 'low' => 'baja',
            default => 'media',
        };
    }

    protected function normalizeBooleanFlag(mixed $value): ?bool
    {
        $normalized = Str::of((string) $value)->trim()->lower()->ascii()->value();

        return match ($normalized) {
            '', null => null,
            '1', 'true', 'yes', 'si', 's' => true,
            '0', 'false', 'no', 'n' => false,
            default => null,
        };
    }

    protected function normalizeDateValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\s*(?:\||;|\r?\n)\s*/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    protected function normalizeAssociativeArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalizedValue = is_array($item)
                ? $this->normalizeAssociativeArray($item)
                : $item;

            if (! is_string($key)) {
                $normalized[$key] = $normalizedValue;

                continue;
            }

            $normalized[$key] = $normalizedValue;

            $alias = $this->normalizeArrayKey($key);

            if ($alias !== '' && ! array_key_exists($alias, $normalized)) {
                $normalized[$alias] = $normalizedValue;
            }
        }

        return $normalized;
    }

    protected function extractFromSources(array $sources, array $keys): mixed
    {
        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            foreach ($keys as $key) {
                if (! array_key_exists($key, $source)) {
                    continue;
                }

                $value = $source[$key];

                if (is_array($value)) {
                    if ($value !== []) {
                        return $value;
                    }

                    continue;
                }

                if (filled($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function normalizeSuggestedCaseType(mixed $value): ?string
    {
        $normalized = Str::of((string) $value)->trim()->lower()->ascii()->replace('-', '_')->replace(' ', '_')->value();

        return match ($normalized) {
            '', null => null,
            SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
            'venta_nuevo_lead',
            'comercial_nuevo_lead' => SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
            SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP,
            'venta_seguimiento',
            'comercial_seguimiento',
            'venta_presupuesto',
            'comercial_presupuesto' => SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP,
            SamuEvent::CLASSIFICATION_POST_SALE_CLAIM,
            'postventa_reclamo' => SamuEvent::CLASSIFICATION_POST_SALE_CLAIM,
            SamuEvent::CLASSIFICATION_POST_SALE_SUPPORT,
            'postventa_soporte' => SamuEvent::CLASSIFICATION_POST_SALE_SUPPORT,
            SamuEvent::CLASSIFICATION_POST_SALE_MAINTENANCE,
            'postventa_mantenimiento' => SamuEvent::CLASSIFICATION_POST_SALE_MAINTENANCE,
            SamuEvent::CLASSIFICATION_POST_SALE_DIAGNOSIS,
            'postventa_diagnostico' => SamuEvent::CLASSIFICATION_POST_SALE_DIAGNOSIS,
            SamuEvent::CLASSIFICATION_POST_SALE_INSTALLATION,
            'postventa_instalacion' => SamuEvent::CLASSIFICATION_POST_SALE_INSTALLATION,
            SamuEvent::CLASSIFICATION_POST_SALE_DISPATCH,
            'postventa_despacho' => SamuEvent::CLASSIFICATION_POST_SALE_DISPATCH,
            SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
            'habilitation',
            'habilitacion',
            'administrative_follow_up',
            'habilitacion_seguimiento',
            'habilitacion_tramite' => SamuEvent::CLASSIFICATION_HABILITATION_FOLLOW_UP,
            SamuEvent::CLASSIFICATION_MIXED,
            'mixto' => SamuEvent::CLASSIFICATION_MIXED,
            SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            'descartar',
            'sin_valor',
            'no_util' => SamuEvent::CLASSIFICATION_UNCLASSIFIED,
            default => null,
        };
    }

    protected function normalizeArrayKey(string $key): string
    {
        return Str::of($key)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();
    }

    protected function normalizeHabilitationStatus(mixed $value): ?string
    {
        $normalized = Str::of((string) $value)->trim()->lower()->ascii()->replace('-', '_')->replace(' ', '_')->value();

        return match ($normalized) {
            '', null, 'no_aplica' => null,
            'pendiente' => 'pendiente',
            'documentacion' => 'documentacion',
            'enviado_gestor', 'enviado_al_gestor' => 'enviado_gestor',
            'en_tramite', 'en_tramite_' => 'en_tramite',
            'aprobado' => 'aprobado',
            'rechazado' => 'rechazado',
            'archivado' => 'archivado',
            default => null,
        };
    }

    protected function normalizePhone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $email = trim(Str::lower((string) $value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    protected function normalizeText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    protected function normalizeDurationMinutes(mixed $value): ?int
    {
        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    protected function resolveClientNameFromPayload(array $payload, array $extractor): ?string
    {
        $explicitName = $this->normalizeText($this->extractFromSources([$payload, $extractor], [
            'client_name',
            'nombre_cliente_erp',
            'cliente',
            'nombre_cliente',
            'contact_name',
            'nombre_del_cliente',
        ]));

        if ($explicitName) {
            return $explicitName;
        }

        $host = Str::lower((string) ($payload['host'] ?? ''));
        $participants = collect($payload['participants'] ?? [])
            ->filter(fn ($participant): bool => is_string($participant) && trim($participant) !== '')
            ->map(fn (string $participant): string => trim($participant))
            ->reject(fn (string $participant): bool => Str::lower($participant) === $host)
            ->values();

        if ($participants->isNotEmpty()) {
            return $participants->join(', ');
        }

        $meetingName = $this->normalizeText($payload['name'] ?? null);

        if ($meetingName && str_contains($meetingName, '<>')) {
            $leftPart = trim((string) Str::before($meetingName, '<>'));

            if ($leftPart !== '') {
                return $leftPart;
            }
        }

        return $meetingName;
    }

    protected function resolvePhoneFromPayload(array $payload, array $extractor): ?string
    {
        $explicitPhone = $this->normalizePhone($this->extractFromSources([$payload, $extractor], [
            'phone',
            'telefono_cliente_erp',
            'telefono',
            'phone_number',
            'telefono_cliente',
        ]));

        if ($explicitPhone) {
            return $explicitPhone;
        }

        $meetingName = (string) ($payload['name'] ?? '');

        if (preg_match('/<>\s*([\+\d][\d\s-]+)/', $meetingName, $matches) === 1) {
            return $this->normalizePhone($matches[1]);
        }

        return null;
    }

    protected function resolveInterestLevelFromPayload(array $payload, array $extractor, ?string $summary): ?string
    {
        $explicit = $this->normalizeInterestLevel($this->extractFromSources([$payload, $extractor], [
            'interest_level',
            'interes_erp',
            'nivel_de_interes',
        ]));

        if ($explicit) {
            return $explicit;
        }

        $yesNoInterest = Str::of((string) $this->extractFromSources([$extractor], [
            'interes_inicial_del_lead',
        ]))->trim()->lower()->ascii()->value();

        if (in_array($yesNoInterest, ['si', 'yes', 'true', '1'], true)) {
            return 'alto';
        }

        if (in_array($yesNoInterest, ['no', 'false', '0'], true)) {
            return 'bajo';
        }

        $text = Str::of(collect([
            $summary,
            $payload['probDesc'] ?? null,
        ])->filter()->implode(' '))
            ->lower()
            ->ascii()
            ->value();

        return match (true) {
            Str::contains($text, ['mostro interes', 'pidio presupuesto', 'alta probabilidad', 'quiere avanzar']) => 'alto',
            Str::contains($text, ['interesado', 'seguimiento', 'acordaron', 'se comprometio']) => 'medio',
            Str::contains($text, ['sin informacion', 'sin interes', 'no avanza']) => 'bajo',
            default => null,
        };
    }

    protected function resolveSeguimientoMedium(?string $channel): string
    {
        return match ($channel) {
            'telefono' => 'llamada_anura_ip',
            'mail' => 'mail',
            default => 'whatsapp',
        };
    }

    protected function resolveStoredPipelineStage(array $normalized, array $classification): ?string
    {
        if (! in_array($classification['classification'], [
            SamuEvent::CLASSIFICATION_COMMERCIAL_LEAD,
            SamuEvent::CLASSIFICATION_COMMERCIAL_FOLLOW_UP,
            SamuEvent::CLASSIFICATION_MIXED,
        ], true)) {
            return null;
        }

        return $normalized['mapped_pipeline_stage'] ?? $normalized['pipeline_stage_raw'];
    }

    protected function resolveHabilitationStatus(array $normalized, ?Habilitation $existing = null): string
    {
        if ($normalized['habilitation_status']) {
            return $normalized['habilitation_status'];
        }

        $text = Str::of(collect([
            $normalized['summary'] ?? null,
            $normalized['transcript'] ?? null,
            $normalized['next_step'] ?? null,
            $normalized['habilitation_blocker'] ?? null,
            $normalized['habilitation_next_action'] ?? null,
        ])->filter()->implode(' '))
            ->lower()
            ->ascii()
            ->value();

        return match (true) {
            Str::contains($text, ['aprobado', 'aprobada', 'habilitado']) => 'aprobado',
            Str::contains($text, ['rechazado', 'rechazada']) => 'rechazado',
            Str::contains($text, ['archivado', 'archivo del tramite']) => 'archivado',
            Str::contains($text, ['en tramite', 'presentado', 'presentacion realizada']) => 'en_tramite',
            Str::contains($text, ['enviado al gestor', 'enviado gestor', 'gestor']) => 'enviado_gestor',
            Str::contains($text, ['apoderamiento', 'documentacion', 'falta firma', 'falta contacto', 'tramite administrativo', 'tad']) => 'documentacion',
            default => $existing?->status ?: 'pendiente',
        };
    }

    protected function shouldAdvanceHabilitationStatus(?string $currentStatus, ?string $suggestedStatus): bool
    {
        if (blank($suggestedStatus) || $currentStatus === $suggestedStatus) {
            return false;
        }

        $order = [
            'pendiente' => 1,
            'documentacion' => 2,
            'enviado_gestor' => 3,
            'en_tramite' => 4,
            'aprobado' => 5,
            'rechazado' => 5,
            'archivado' => 5,
        ];

        return ($order[$suggestedStatus] ?? 0) >= ($order[$currentStatus] ?? 0);
    }

    protected function resolveHabilitationEquipment(array $normalized, ?Habilitation $existing = null): string
    {
        $candidate = $normalized['tasks_detected'][0]['title']
            ?? $normalized['summary']
            ?? $normalized['habilitation_next_action']
            ?? $existing?->equipment
            ?? null;

        return $candidate
            ? Str::limit($candidate, 255, '')
            : 'Habilitacion administrativa Samu.ai';
    }

    protected function shouldUpdateHabilitationEquipment(?string $currentValue, string $candidate): bool
    {
        $current = trim((string) $currentValue);

        if ($candidate === '') {
            return false;
        }

        if ($current === '') {
            return true;
        }

        return in_array($current, [
            'Habilitacion administrativa Samu.ai',
            'Tramite administrativo Samu.ai',
        ], true) && ! in_array($candidate, [
            'Habilitacion administrativa Samu.ai',
            'Tramite administrativo Samu.ai',
        ], true);
    }

    protected function resolveHabilitationNextManagementDate(array $normalized): ?string
    {
        if (filled($normalized['explicit_follow_up_date'])) {
            return $normalized['explicit_follow_up_date'];
        }

        $taskDate = collect($normalized['tasks_detected'] ?? [])
            ->pluck('due_date')
            ->filter()
            ->sort()
            ->first();

        return is_string($taskDate) ? $taskDate : null;
    }

    protected function buildHabilitationObservations(SamuEvent $event, array $normalized, string $marker): string
    {
        return collect([
            now()->format('d/m/Y H:i') . ' | Samu.ai',
            $normalized['summary'] ? 'Resumen: ' . $normalized['summary'] : null,
            $normalized['next_step'] ? 'Proximo paso: ' . $normalized['next_step'] : null,
            $normalized['habilitation_next_action'] ? 'Gestion sugerida: ' . $normalized['habilitation_next_action'] : null,
            $normalized['habilitation_blocker'] ? 'Bloqueante: ' . $normalized['habilitation_blocker'] : null,
            $normalized['next_step_owner'] ? 'Responsable siguiente paso: ' . $normalized['next_step_owner'] : null,
            $normalized['explicit_follow_up_date'] ? 'Fecha mencionada: ' . Carbon::parse($normalized['explicit_follow_up_date'])->format('d/m/Y') : null,
            filled($normalized['external_id']) ? 'External ID: ' . $normalized['external_id'] : null,
            $marker,
        ])->filter()->implode(PHP_EOL);
    }

    protected function buildManualClassificationReason(SamuEvent $event, string $classification): string
    {
        return collect([
            'Recatalogado manualmente como ' . SamuEvent::getClassificationLabel($classification) . '.',
            filled($event->manual_review_notes) ? 'Nota: ' . trim((string) $event->manual_review_notes) : null,
        ])->filter()->implode(' ');
    }

    protected function buildSeguimientoNotes(
        SamuEvent $event,
        array $normalized,
        array $classification,
        string $marker,
    ): string {
        return collect([
            'Tarea comercial creada automaticamente desde Samu.ai.',
            'Clasificacion: ' . SamuEvent::getClassificationLabel($classification['classification']),
            $normalized['summary'] ? 'Resumen: ' . $normalized['summary'] : null,
            $normalized['interest_level'] ? 'Interes: ' . SamuEvent::getInterestLevelLabel($normalized['interest_level']) : null,
            $normalized['objections'] !== [] ? 'Objeciones: ' . implode(', ', $normalized['objections']) : null,
            filled($normalized['external_id']) ? 'External ID: ' . $normalized['external_id'] : null,
            $marker,
        ])->filter()->implode(PHP_EOL);
    }

    protected function buildPendienteNotes(
        SamuEvent $event,
        array $normalized,
        ?Claim $claim,
        string $marker,
    ): string {
        return collect([
            'Pendiente de postventa generado desde Samu.ai.',
            $normalized['summary'] ? 'Resumen: ' . $normalized['summary'] : null,
            $normalized['next_step'] ? 'Proximo paso: ' . $normalized['next_step'] : null,
            $normalized['objections'] !== [] ? 'Objeciones: ' . implode(', ', $normalized['objections']) : null,
            $claim ? 'Claim relacionado #' . $claim->id : null,
            filled($normalized['external_id']) ? 'External ID: ' . $normalized['external_id'] : null,
            $marker,
        ])->filter()->implode(PHP_EOL);
    }

    protected function buildClaimDescription(SamuEvent $event, array $normalized, string $marker): string
    {
        return collect([
            $normalized['summary'] ?: 'Reclamo detectado automaticamente desde Samu.ai.',
            $normalized['next_step'] ? 'Proximo paso sugerido: ' . $normalized['next_step'] : null,
            $normalized['objections'] !== [] ? 'Objeciones registradas: ' . implode(', ', $normalized['objections']) : null,
            filled($normalized['transcript']) ? 'Transcripcion: ' . Str::limit($normalized['transcript'], 1500) : null,
            filled($normalized['external_id']) ? 'External ID: ' . $normalized['external_id'] : null,
            $marker,
        ])->filter()->implode(PHP_EOL . PHP_EOL);
    }
}
