<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Seguimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommercialDemoDataService
{
    protected const DEMO_EMAIL_DOMAIN = 'prueba.local';

    protected const FIRST_NAMES = [
        'Valeria',
        'Claudio',
        'Marta',
        'Nestor',
        'Lucia',
        'Ramiro',
        'Silvia',
        'Jorge',
        'Carolina',
        'Dario',
        'Patricia',
        'Hector',
        'Cecilia',
        'Mario',
        'Agustina',
        'Fabian',
        'Noelia',
        'Oscar',
    ];

    protected const LAST_NAMES = [
        'Gimenez',
        'Rossi',
        'Fernandez',
        'Pereyra',
        'Lopez',
        'Molina',
        'Suarez',
        'Acosta',
        'Benitez',
        'Ledesma',
        'Alvarez',
        'Roldan',
        'Herrera',
        'Quiroga',
        'Mendez',
        'Ibarra',
        'Correa',
        'Sanchez',
    ];

    protected const LOCALITIES = [
        'Rosario (prueba)',
        'Funes (prueba)',
        'Roldan (prueba)',
        'San Lorenzo (prueba)',
        'Casilda (prueba)',
        'Villa Gobernador Galvez (prueba)',
        'Perez (prueba)',
        'Granadero Baigorria (prueba)',
    ];

    protected const ZONES = [
        'Zona Norte (prueba)',
        'Zona Centro (prueba)',
        'Zona Sur (prueba)',
        'Corredor Oeste (prueba)',
    ];

    protected const PRODUCTS = [
        'Silla salvaescalera recta (prueba)',
        'Silla salvaescalera curva (prueba)',
        'Plataforma vertical compacta (prueba)',
        'Elevador residencial basico (prueba)',
        'Plataforma inclinada tramo corto (prueba)',
        'Ascensor domestico compacto (prueba)',
    ];

    protected const CHANNELS = [
        'whatsapp',
        'redes_sociales',
        'mail',
        'telefono',
    ];

    protected const CONTACT_MEDIA = [
        'whatsapp',
        'llamada_anura_ip',
        'mail',
        'visita',
        'videollamada',
    ];

    protected string $crmPrefix = '';

    protected int $crmSequence = 1;

    public function seed(int $count = 18, bool $append = false): array
    {
        $count = max(1, $count);
        $commercials = User::role('comercial')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get();

        if ($commercials->isEmpty()) {
            throw new RuntimeException('No hay usuarios con rol comercial para asignar leads de prueba.');
        }

        $existingDemoLeads = $this->countExistingDemoLeads();

        if ($existingDemoLeads > 0 && ! $append) {
            throw new RuntimeException("Ya existen {$existingDemoLeads} leads de prueba. Usa --append si queres sumar otra tanda.");
        }

        return DB::transaction(function () use ($count, $commercials, $existingDemoLeads): array {
            $this->bootCrmSequence();

            $summary = [
                'existing_demo_leads' => $existingDemoLeads,
                'created_leads' => 0,
                'created_seguimientos' => 0,
                'created_presupuestos' => 0,
                'created_ventas' => 0,
                'created_histories' => 0,
                'commercials' => $commercials->pluck('name')->all(),
                'stage_breakdown' => [],
            ];

            $templates = $this->scenarioTemplates();

            for ($index = 0; $index < $count; $index++) {
                $template = $templates[$index % count($templates)];
                /** @var User $commercial */
                $commercial = $commercials->get($index % $commercials->count());
                $baseDate = $this->resolveLeadDate($index, $count);

                $result = $this->createScenario(
                    template: $template,
                    commercial: $commercial,
                    baseDate: $baseDate,
                    ordinal: $index + 1,
                );

                $summary['created_leads']++;
                $summary['created_seguimientos'] += $result['seguimientos'];
                $summary['created_presupuestos'] += $result['presupuestos'];
                $summary['created_ventas'] += $result['ventas'];
                $summary['created_histories'] += $result['histories'];
                $summary['stage_breakdown'][$template['stage']] = ($summary['stage_breakdown'][$template['stage']] ?? 0) + 1;
            }

            ksort($summary['stage_breakdown']);

            return $summary;
        });
    }

    protected function countExistingDemoLeads(): int
    {
        return (int) DB::table('leads')
            ->where('email', 'like', '%@' . self::DEMO_EMAIL_DOMAIN)
            ->count();
    }

    protected function bootCrmSequence(): void
    {
        $this->crmPrefix = now()->format('Ymd');

        $lastCrmId = DB::table('leads')
            ->where('crm_id', 'like', $this->crmPrefix . '-%')
            ->lockForUpdate()
            ->orderByDesc('crm_id')
            ->value('crm_id');

        $this->crmSequence = 1;

        if (is_string($lastCrmId) && preg_match('/^\d{8}-(\d{4})$/', $lastCrmId, $matches) === 1) {
            $this->crmSequence = ((int) $matches[1]) + 1;
        }
    }

    protected function nextCrmId(): string
    {
        $crmId = $this->crmPrefix . '-' . str_pad((string) $this->crmSequence, 4, '0', STR_PAD_LEFT);
        $this->crmSequence++;

        return $crmId;
    }

    protected function scenarioTemplates(): array
    {
        return [
            [
                'key' => 'inbound_new',
                'stage' => Lead::STAGE_INGRESADO,
                'completed_followups' => 0,
                'pending_due_offset' => 2,
                'last_touch_days' => 0,
                'orientation' => false,
                'budget' => false,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [],
            ],
            [
                'key' => 'contacted_active',
                'stage' => Lead::STAGE_CONTACTADO,
                'completed_followups' => 1,
                'pending_due_offset' => 2,
                'last_touch_days' => 1,
                'orientation' => false,
                'budget' => false,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO],
            ],
            [
                'key' => 'contacted_overdue',
                'stage' => Lead::STAGE_CONTACTADO,
                'completed_followups' => 1,
                'pending_due_offset' => -2,
                'last_touch_days' => 5,
                'orientation' => false,
                'budget' => false,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO],
            ],
            [
                'key' => 'orientation_active',
                'stage' => Lead::STAGE_ORIENTACION,
                'completed_followups' => 2,
                'pending_due_offset' => 4,
                'last_touch_days' => 1,
                'orientation' => true,
                'budget' => false,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION],
            ],
            [
                'key' => 'quote_without_budget',
                'stage' => Lead::STAGE_COTIZACION,
                'completed_followups' => 2,
                'pending_due_offset' => -1,
                'last_touch_days' => 2,
                'orientation' => true,
                'budget' => false,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION, Lead::STAGE_COTIZACION],
            ],
            [
                'key' => 'budget_active',
                'stage' => Lead::STAGE_PRESUPUESTO,
                'completed_followups' => 3,
                'pending_due_offset' => 5,
                'last_touch_days' => 1,
                'orientation' => true,
                'budget' => true,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION, Lead::STAGE_COTIZACION, Lead::STAGE_PRESUPUESTO],
            ],
            [
                'key' => 'budget_overdue',
                'stage' => Lead::STAGE_PRESUPUESTO,
                'completed_followups' => 3,
                'pending_due_offset' => -3,
                'last_touch_days' => 6,
                'orientation' => true,
                'budget' => true,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION, Lead::STAGE_COTIZACION, Lead::STAGE_PRESUPUESTO],
            ],
            [
                'key' => 'won_closed',
                'stage' => Lead::STAGE_VENTA_CERRADA,
                'completed_followups' => 3,
                'pending_due_offset' => null,
                'last_touch_days' => 3,
                'orientation' => true,
                'budget' => true,
                'sale' => true,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION, Lead::STAGE_COTIZACION, Lead::STAGE_PRESUPUESTO, Lead::STAGE_VENTA_CERRADA],
            ],
            [
                'key' => 'lost_after_budget',
                'stage' => Lead::STAGE_PERDIDO,
                'completed_followups' => 2,
                'pending_due_offset' => null,
                'last_touch_days' => 4,
                'orientation' => true,
                'budget' => true,
                'sale' => false,
                'loss_reason' => 'Precio',
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION, Lead::STAGE_COTIZACION, Lead::STAGE_PRESUPUESTO, Lead::STAGE_PERDIDO],
            ],
            [
                'key' => 'postponed',
                'stage' => Lead::STAGE_POSTERGADO,
                'completed_followups' => 2,
                'pending_due_offset' => 10,
                'last_touch_days' => 3,
                'orientation' => true,
                'budget' => false,
                'sale' => false,
                'loss_reason' => null,
                'pipeline_path' => [Lead::STAGE_CONTACTADO, Lead::STAGE_ORIENTACION, Lead::STAGE_POSTERGADO],
            ],
        ];
    }

    protected function resolveLeadDate(int $index, int $count): Carbon
    {
        $today = now()->startOfDay();
        $currentMonthCount = (int) ceil($count * 0.7);

        if ($index < $currentMonthCount) {
            $availableDays = max($today->day - 1, 1);
            $step = max((int) floor($availableDays / max($currentMonthCount, 1)), 1);
            $dayOffset = min($index * $step, $availableDays);

            return $today->copy()
                ->startOfMonth()
                ->addDays($dayOffset)
                ->setTime(9 + ($index % 6), 15);
        }

        $historicIndex = $index - $currentMonthCount;
        $monthOffset = 1 + ($historicIndex % 2);
        $targetMonth = $today->copy()->subMonths($monthOffset)->startOfMonth();
        $targetDay = min(4 + ($historicIndex * 3), max($targetMonth->daysInMonth - 1, 1));

        return $targetMonth->copy()
            ->addDays($targetDay)
            ->setTime(10 + ($index % 5), 30);
    }

    protected function createScenario(array $template, User $commercial, Carbon $baseDate, int $ordinal): array
    {
        $nameIndex = ($ordinal - 1) % count(self::FIRST_NAMES);
        $productIndex = ($ordinal - 1) % count(self::PRODUCTS);
        $localityIndex = ($ordinal - 1) % count(self::LOCALITIES);
        $zoneIndex = ($ordinal - 1) % count(self::ZONES);
        $channelIndex = ($ordinal - 1) % count(self::CHANNELS);

        $firstName = self::FIRST_NAMES[$nameIndex];
        $lastName = self::LAST_NAMES[$nameIndex] . ' (prueba)';
        $product = self::PRODUCTS[$productIndex];
        $locality = self::LOCALITIES[$localityIndex];
        $zone = self::ZONES[$zoneIndex];
        $channel = self::CHANNELS[$channelIndex];
        $emailSlug = str_pad((string) $ordinal, 3, '0', STR_PAD_LEFT);
        $phoneSuffix = str_pad((string) (7000 + $ordinal), 4, '0', STR_PAD_LEFT);

        $lastTouchAt = $template['completed_followups'] > 0
            ? $this->clampNotBefore(
                now()->copy()->subDays((int) $template['last_touch_days'])->setTime(15, 0),
                $baseDate->copy()->addDay(),
            )
            : $baseDate->copy();

        $completedContactDates = $this->buildCompletedTouchDates(
            baseDate: $baseDate,
            lastTouchAt: $lastTouchAt,
            count: (int) $template['completed_followups'],
        );

        $closedAt = null;

        if (in_array($template['stage'], Lead::CLOSED_PIPELINES, true)) {
            $closedAt = $this->clampNotBefore(
                $lastTouchAt->copy()->addDay()->setTime(17, 30),
                $baseDate->copy()->addDays(2),
            );

            if ($closedAt->greaterThan(now())) {
                $closedAt = now()->copy()->subHours(2);
            }
        }

        $crmId = $this->nextCrmId();

        $leadId = DB::table('leads')->insertGetId([
            'crm_id' => $crmId,
            'fecha_ingreso' => $baseDate->toDateString(),
            'hora_ingreso' => $baseDate->format('H:i:s'),
            'comercial_asignado_id' => $commercial->id,
            'canal_origen' => $channel,
            'nombre' => $firstName,
            'apellido' => $lastName,
            'telefono' => '341555' . $phoneSuffix,
            'email' => 'lead' . $emailSlug . '.' . strtolower(str_replace('-', '', $crmId)) . '.' . str($template['key'])->slug()->value() . '@' . self::DEMO_EMAIL_DOMAIN,
            'localidad' => $locality,
            'provincia' => 'Santa Fe (prueba)',
            'zona_comercial' => $zone,
            'tipo_cliente' => $ordinal % 3 === 0 ? 'Empresa' : 'Residencial',
            'subtipo_publico' => null,
            'producto_interes' => $product,
            'tipo_instalacion' => ['Recta', 'Curva', 'Exterior', 'Piscina', 'Vertical'][$ordinal % 5],
            'documentacion_cliente' => 'Registro de prueba generado para poblar metricas comerciales (prueba).',
            'documentacion_cliente_archivos' => null,
            'cliente_envio_fotos' => $ordinal % 2 === 0,
            'cliente_envio_planos' => $ordinal % 3 === 0,
            'requiere_relevamiento_pago' => $ordinal % 4 === 0,
            'orientacion_dada' => $template['orientation'],
            'tipo_orientacion' => $template['orientation'] ? ($ordinal % 2 === 0 ? 'Texto WhatsApp' : 'PDF enviado por Mail') : null,
            'fecha_orientacion' => $template['orientation']
                ? ($completedContactDates[1] ?? $completedContactDates[0] ?? $baseDate->copy()->addDay())->toDateString()
                : null,
            'estado_pipeline' => $template['stage'],
            'resultado_final' => match ($template['stage']) {
                Lead::STAGE_VENTA_CERRADA => Lead::RESULTADO_VENDIDO,
                Lead::STAGE_PERDIDO => Lead::RESULTADO_PERDIDO,
                default => Lead::RESULTADO_ABIERTO,
            },
            'motivo_perdida' => $template['loss_reason'],
            'fecha_cierre' => $closedAt?->toDateString(),
            'area_responsable' => 'Comercial Venta',
            'created_by' => $commercial->id,
            'created_at' => $baseDate,
            'updated_at' => $closedAt ?? $lastTouchAt ?? $baseDate,
            'fecha_ultimo_seguimiento' => $completedContactDates !== []
                ? end($completedContactDates)->toDateString()
                : $baseDate->toDateString(),
            'estado' => 'activo',
            'notificado' => false,
        ]);

        $histories = 0;
        $seguimientos = 0;
        $presupuestos = 0;
        $ventas = 0;

        $histories += $this->insertLeadHistory(
            leadId: $leadId,
            userId: $commercial->id,
            eventKey: 'created',
            title: 'Lead de prueba ingresado',
            description: "Cliente {$firstName} {$lastName}. Canal {$channel}. Producto {$product}. Registro de prueba para metricas (prueba).",
            statusFrom: null,
            statusTo: Lead::STAGE_INGRESADO,
            changedAt: $baseDate,
            meta: ['demo' => true, 'scenario' => $template['key']],
        );

        foreach ($completedContactDates as $touchIndex => $touchAt) {
            $seguimientoId = DB::table('seguimientos')->insertGetId([
                'lead_id' => $leadId,
                'fecha_contacto' => $touchAt->toDateString(),
                'comercial_id' => $commercial->id,
                'medio_contacto' => self::CONTACT_MEDIA[$touchIndex % count(self::CONTACT_MEDIA)],
                'resultado' => 'Seguimiento comercial completado (prueba).',
                'proxima_accion' => 'Continuar gestion comercial y calificar necesidad (prueba).',
                'fecha_proxima_accion' => $touchAt->copy()->addDays(2)->toDateString(),
                'observaciones' => 'Seguimiento generado automaticamente para metricas (prueba).',
                'estado' => Seguimiento::STATUS_COMPLETADO,
                'created_at' => $touchAt,
                'updated_at' => $touchAt,
            ]);

            $seguimientos += $seguimientoId > 0 ? 1 : 0;

            $histories += $this->insertLeadHistory(
                leadId: $leadId,
                userId: $commercial->id,
                eventKey: 'follow_up_logged',
                title: 'Seguimiento de prueba registrado',
                description: 'Seguimiento completado con resultado positivo (prueba).',
                statusFrom: Lead::STAGE_INGRESADO,
                statusTo: Lead::STAGE_INGRESADO,
                changedAt: $touchAt->copy()->setTime(16, 0),
                meta: ['demo' => true, 'type' => 'completed_follow_up'],
            );
        }

        $pendingFollowUpDueAt = $this->resolvePendingFollowUpDueDate($template['pending_due_offset']);

        if (! in_array($template['stage'], Lead::CLOSED_PIPELINES, true) && $pendingFollowUpDueAt !== null) {
            $pendingCreatedAt = $this->clampNotBefore(
                $pendingFollowUpDueAt->copy()->subDay()->setTime(9, 30),
                $completedContactDates !== [] ? end($completedContactDates)->copy()->setTime(18, 0) : $baseDate->copy()->addHours(3),
            );

            $seguimientoId = DB::table('seguimientos')->insertGetId([
                'lead_id' => $leadId,
                'fecha_contacto' => null,
                'comercial_id' => $commercial->id,
                'medio_contacto' => self::CONTACT_MEDIA[$ordinal % count(self::CONTACT_MEDIA)],
                'resultado' => null,
                'proxima_accion' => 'Retomar contacto y validar cierre comercial (prueba).',
                'fecha_proxima_accion' => $pendingFollowUpDueAt->toDateString(),
                'observaciones' => 'Seguimiento pendiente creado para tablero y alertas (prueba).',
                'estado' => Seguimiento::STATUS_PENDIENTE,
                'created_at' => $pendingCreatedAt,
                'updated_at' => $pendingCreatedAt,
            ]);

            $seguimientos += $seguimientoId > 0 ? 1 : 0;

            $histories += $this->insertLeadHistory(
                leadId: $leadId,
                userId: $commercial->id,
                eventKey: 'follow_up_created',
                title: 'Seguimiento de prueba programado',
                description: 'Seguimiento pendiente agendado para continuar gestion (prueba).',
                statusFrom: $template['stage'],
                statusTo: $template['stage'],
                changedAt: $pendingCreatedAt->copy()->setTime(10, 15),
                meta: ['demo' => true, 'type' => 'pending_follow_up'],
            );
        }

        $pipelineEvents = $this->buildPipelineEvents(
            pipelinePath: $template['pipeline_path'],
            baseDate: $baseDate,
            completedContactDates: $completedContactDates,
            lastTouchAt: $lastTouchAt,
            closedAt: $closedAt,
        );

        foreach ($pipelineEvents as $event) {
            $histories += $this->insertLeadHistory(
                leadId: $leadId,
                userId: $commercial->id,
                eventKey: 'pipeline_updated',
                title: $event['title'],
                description: $event['description'],
                statusFrom: $event['from'],
                statusTo: $event['to'],
                changedAt: $event['at'],
                meta: ['demo' => true, 'type' => 'pipeline_change'],
            );
        }

        $quoteDate = $this->resolveQuoteDate($completedContactDates, $baseDate, $lastTouchAt);
        $budgetDate = $this->resolveBudgetDate($completedContactDates, $quoteDate, $lastTouchAt, $closedAt);

        if ($template['budget']) {
            $itemOne = 1500000 + ($ordinal * 25000);
            $itemTwo = 420000 + ($ordinal * 10000);
            $budgetTotal = $itemOne + $itemTwo;

            DB::table('presupuestos')->insert([
                'lead_id' => $leadId,
                'producto_1' => $product,
                'precio_1' => $itemOne,
                'producto_2' => 'Instalacion y puesta en marcha (prueba)',
                'precio_2' => $itemTwo,
                'producto_3' => null,
                'precio_3' => null,
                'producto_4' => null,
                'precio_4' => null,
                'presupuesto_definitivo' => $budgetTotal,
                'fecha_envio' => $budgetDate->toDateString(),
                'created_by' => $commercial->id,
                'created_at' => $budgetDate,
                'updated_at' => $budgetDate,
            ]);

            $presupuestos++;
        }

        if ($template['sale']) {
            $saleAmount = 2250000 + ($ordinal * 85000);
            $saleDate = $closedAt ?? $budgetDate->copy()->addDay();

            DB::table('ventas')->insert([
                'lead_id' => $leadId,
                'producto_instalado' => $product,
                'monto_total' => $saleAmount,
                'estado' => 'Cerrada',
                'fecha_cierre' => $saleDate->toDateString(),
                'created_by' => $commercial->id,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            $ventas++;
        }

        $latestTimestamp = collect([
            $baseDate,
            $lastTouchAt,
            $closedAt,
            $budgetDate,
            $pendingFollowUpDueAt?->copy()->setTime(9, 30),
        ])->filter()->sortByDesc(fn (Carbon $date): int => $date->getTimestamp())->first();

        DB::table('leads')
            ->where('id', $leadId)
            ->update([
                'updated_at' => $latestTimestamp ?? $baseDate,
            ]);

        return [
            'seguimientos' => $seguimientos,
            'presupuestos' => $presupuestos,
            'ventas' => $ventas,
            'histories' => $histories,
        ];
    }

    protected function buildCompletedTouchDates(Carbon $baseDate, Carbon $lastTouchAt, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        if ($count === 1) {
            return [$this->clampNotBefore($lastTouchAt->copy()->setTime(15, 0), $baseDate->copy()->addDay())];
        }

        $dates = [];
        $windowDays = max($baseDate->diffInDays($lastTouchAt), 1);

        for ($index = 0; $index < $count; $index++) {
            $ratio = ($index + 1) / $count;
            $candidate = $baseDate->copy()
                ->addDays(max((int) floor($windowDays * $ratio), 1))
                ->setTime(11 + $index, 0);

            if ($candidate->greaterThan($lastTouchAt)) {
                $candidate = $lastTouchAt->copy()->setTime(15, 0);
            }

            if ($dates !== [] && $candidate->lessThanOrEqualTo(end($dates))) {
                $candidate = end($dates)->copy()->addDay()->setTime(11 + $index, 0);
            }

            if ($candidate->greaterThan($lastTouchAt)) {
                $candidate = $lastTouchAt->copy()->setTime(15, 0);
            }

            $dates[] = $candidate;
        }

        return $dates;
    }

    protected function resolvePendingFollowUpDueDate(?int $offset): ?Carbon
    {
        if ($offset === null) {
            return null;
        }

        return now()->copy()->addDays($offset)->setTime(9, 0);
    }

    protected function buildPipelineEvents(
        array $pipelinePath,
        Carbon $baseDate,
        array $completedContactDates,
        Carbon $lastTouchAt,
        ?Carbon $closedAt,
    ): array {
        if ($pipelinePath === []) {
            return [];
        }

        $events = [];
        $previousStage = Lead::STAGE_INGRESADO;
        $cursor = $baseDate->copy()->addHours(4);

        foreach ($pipelinePath as $index => $stage) {
            $candidate = match ($stage) {
                Lead::STAGE_CONTACTADO => $completedContactDates[0] ?? $baseDate->copy()->addDay()->setTime(16, 30),
                Lead::STAGE_ORIENTACION => $completedContactDates[min(1, max(count($completedContactDates) - 1, 0))] ?? $cursor->copy()->addDay(),
                Lead::STAGE_COTIZACION => ($completedContactDates[min(1, max(count($completedContactDates) - 1, 0))] ?? $lastTouchAt)->copy()->addHours(4),
                Lead::STAGE_PRESUPUESTO => $lastTouchAt->copy()->setTime(17, 0),
                Lead::STAGE_VENTA_CERRADA, Lead::STAGE_PERDIDO => $closedAt ?? $lastTouchAt->copy()->addDay()->setTime(17, 30),
                Lead::STAGE_POSTERGADO => $lastTouchAt->copy()->setTime(16, 45),
                default => $cursor->copy()->addDay(),
            };

            if ($candidate->lessThanOrEqualTo($cursor)) {
                $candidate = $cursor->copy()->addHours(6);
            }

            $events[] = [
                'from' => $previousStage,
                'to' => $stage,
                'at' => $candidate,
                'title' => $stage === Lead::STAGE_VENTA_CERRADA
                    ? 'Lead de prueba cerrado como venta'
                    : ($stage === Lead::STAGE_PERDIDO
                        ? 'Lead de prueba marcado como perdido'
                        : 'Pipeline de prueba actualizado'),
                'description' => 'Cambio de etapa comercial para metricas (prueba).',
            ];

            $previousStage = $stage;
            $cursor = $candidate->copy();
        }

        return $events;
    }

    protected function resolveQuoteDate(array $completedContactDates, Carbon $baseDate, Carbon $lastTouchAt): Carbon
    {
        $candidate = $completedContactDates[1] ?? $completedContactDates[0] ?? $baseDate->copy()->addDays(2);

        return $this->clampNotBefore($candidate->copy()->addHours(4), $baseDate->copy()->addDay());
    }

    protected function resolveBudgetDate(array $completedContactDates, Carbon $quoteDate, Carbon $lastTouchAt, ?Carbon $closedAt): Carbon
    {
        $candidate = end($completedContactDates) ?: $lastTouchAt;
        $budgetDate = $this->clampNotBefore($candidate->copy()->addHours(5), $quoteDate->copy());

        if ($closedAt && $budgetDate->greaterThanOrEqualTo($closedAt)) {
            $budgetDate = $closedAt->copy()->subHours(6);
        }

        return $budgetDate;
    }

    protected function clampNotBefore(Carbon $candidate, Carbon $minimum): Carbon
    {
        return $candidate->lessThan($minimum) ? $minimum : $candidate;
    }

    protected function insertLeadHistory(
        int $leadId,
        int $userId,
        string $eventKey,
        string $title,
        string $description,
        ?string $statusFrom,
        ?string $statusTo,
        Carbon $changedAt,
        array $meta = [],
    ): int {
        DB::table('lead_histories')->insert([
            'lead_id' => $leadId,
            'user_id' => $userId,
            'event_key' => $eventKey,
            'title' => $title,
            'description' => $description,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'meta' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'changed_at' => $changedAt,
            'created_at' => $changedAt,
            'updated_at' => $changedAt,
        ]);

        return 1;
    }
}
