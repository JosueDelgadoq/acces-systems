<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pendiente extends Model
{
    public const STAGE_POSTVENTA = 'postventa';
    public const STAGE_ALFREDO_REVIEW = 'alfredo_review';
    public const STAGE_ASSIGNED = 'assigned';
    public const STAGE_ACCEPTED = 'accepted';
    public const STAGE_COMPLETED = 'completed';
    public const STAGE_CANCELLED = 'cancelled';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_IN_PROGRESS => 'En proceso',
        self::STATUS_COMPLETED => 'Finalizado',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    public const PRIORITY_OPTIONS = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
    ];

    public const TYPE_OPTIONS = [
        'reclamo' => 'Reclamo',
        'instalacion' => 'Instalacion',
        'desinstalacion' => 'Desinstalacion',
        'soporte' => 'Cambio de bateria',
        'diagnostico' => 'Visita tecnica de diagnostico',
        'mantenimiento' => 'Visita tecnica de mantenimiento',
        'reparacion' => 'Reparacion',
        'presupuesto' => 'Presupuestar',
        'Despacho' => 'Despacho',
        'Repuesto' => 'Repuesto',
        'capacitacion' => 'capacitacion',


        ];

    public const WORKFLOW_STAGE_OPTIONS = [
        self::STAGE_POSTVENTA => 'Carga Posventa',
        self::STAGE_ALFREDO_REVIEW => 'Revision Alfredo',
        self::STAGE_ASSIGNED => 'Asignado',
        self::STAGE_ACCEPTED => 'Firmado por tecnico',
        self::STAGE_COMPLETED => 'Cerrado',
        self::STAGE_CANCELLED => 'Cancelado',
    ];

    public const OPERATIONAL_ZONE_OPTIONS = [
        'norte' => 'Zona Norte',
        'sur' => 'Zona Sur',
        'este' => 'Zona Este',
        'oeste' => 'Zona Oeste',
        'centro' => 'Zona Centro',
        'interior' => 'Interior',
        'especial' => 'Cobertura especial',
    ];

    public const TOOL_OPTIONS_BY_TYPE = [
        'reclamo' => [
            'orden_diagnostico' => 'Orden de diagnostico',
            'multimetro' => 'Multimetro',
            'kit_basico' => 'Kit de herramientas basico',
            'epp' => 'Elementos de proteccion personal',
        ],
        'instalacion' => [
            'manual_instalacion' => 'Manual de instalacion',
            'taladro' => 'Taladro',
            'kit_anclajes' => 'Kit de anclajes',
            'nivel' => 'Nivel',
            'epp' => 'Elementos de proteccion personal',
        ],
        'desinstalacion' => [
            'kit_basico' => 'Kit de herramientas basico',
            'embalaje' => 'Material de embalaje',
            'cintas' => 'Cintas y etiquetas',
            'epp' => 'Elementos de proteccion personal',
        ],
        'soporte' => [
            'bateria_repuesto' => 'Bateria o repuesto',
            'cargador' => 'Cargador / fuente',
            'kit_basico' => 'Kit de herramientas basico',
            'multimetro' => 'Multimetro',
        ],
        'diagnostico' => [
            'orden_diagnostico' => 'Orden de diagnostico',
            'multimetro' => 'Multimetro',
            'checklist_fallas' => 'Checklist de fallas',
            'kit_basico' => 'Kit de herramientas basico',
        ],
        'mantenimiento' => [
            'checklist_mantenimiento' => 'Checklist de mantenimiento',
            'lubricantes' => 'Lubricantes / insumos',
            'kit_basico' => 'Kit de herramientas basico',
            'epp' => 'Elementos de proteccion personal',
        ],
        'reparacion' => [
            'orden_reparacion' => 'Orden de reparacion',
            'repuestos' => 'Repuestos definidos',
            'multimetro' => 'Multimetro',
            'kit_basico' => 'Kit de herramientas basico',
        ],
        'presupuesto' => [
            'planilla_relevamiento' => 'Planilla de relevamiento',
            'camara' => 'Camara / fotos',
            'kit_basico' => 'Kit de herramientas basico',
        ],
    ];

    protected $fillable = [
        'client_id',
        'service_address',
        'neighborhood',
        'operational_zone',
        'user_id',
        'reviewed_by_user_id',
        'assigned_by_user_id',
        'type',
        'workflow_stage',
        'suggested_priority',
        'client_availability',
        'description',
        'initial_diagnosis',
        'possible_spare_parts',
        'status',
        'priority',
        'notes',
        'review_notes',
        'due_date',
        'assigned_at',
        'estimated_time',
        'completed_at',
        'remito',
        'source',
        'service_order_number',
        'work_order',
        'required_tools',
        'technician_acknowledged',
        'technician_signature_name',
        'technician_signature_at',
        'technician_signature_notes',
        'post_visit_acknowledged',
        'post_visit_signature_name',
        'post_visit_signature_at',
        'post_visit_signature_notes',
        'control_summary',
        'performed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'due_date' => 'datetime:d/m/Y',
        'assigned_at' => 'datetime',
        'required_tools' => 'array',
        'technician_acknowledged' => 'boolean',
        'technician_signature_at' => 'datetime',
        'post_visit_acknowledged' => 'boolean',
        'post_visit_signature_at' => 'datetime',
        'performed_at' => 'datetime',
    ];

    public static function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public static function supportsTechnicalBoard(): bool
    {
        return self::hasColumn('workflow_stage');
    }

    public static function hasColumn(string $column): bool
    {
        return app(\App\Services\PendienteSchemaService::class)->hasColumn($column);
    }

    public static function hasColumns(array $columns): bool
    {
        return app(\App\Services\PendienteSchemaService::class)->hasColumns($columns);
    }

    public static function getPriorityOptions(): array
    {
        return self::PRIORITY_OPTIONS;
    }

    public static function getTypeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }

    public static function getWorkflowStageOptions(): array
    {
        return self::WORKFLOW_STAGE_OPTIONS;
    }

    public static function getOperationalZoneOptions(): array
    {
        return self::OPERATIONAL_ZONE_OPTIONS;
    }

    public static function getStatusLabel(?string $status): string
    {
        return self::STATUS_OPTIONS[$status]
            ?? (string) str($status)->replace('_', ' ')->title();
    }

    public static function getWorkflowStageLabel(?string $stage): string
    {
        return self::WORKFLOW_STAGE_OPTIONS[$stage]
            ?? (string) str($stage)->replace('_', ' ')->title();
    }

    public static function getToolOptionsForType(?string $type): array
    {
        return self::TOOL_OPTIONS_BY_TYPE[$type] ?? [
            'kit_basico' => 'Kit de herramientas basico',
            'epp' => 'Elementos de proteccion personal',
        ];
    }

    public static function normalizeRequiredTools(mixed $value, ?string $type): array
    {
        $tools = collect($value);

        if (blank($value)) {
            $tools = collect(array_keys(self::getToolOptionsForType($type)));
        }

        return $tools
            ->filter(fn ($tool) => filled($tool))
            ->values()
            ->all();
    }

    public static function resolveWorkflowStage(self $pendiente): string
    {
        if ($pendiente->status === self::STATUS_COMPLETED) {
            return self::STAGE_COMPLETED;
        }

        if ($pendiente->status === self::STATUS_CANCELLED) {
            return self::STAGE_CANCELLED;
        }

        if ($pendiente->technician_acknowledged || filled($pendiente->technician_signature_at)) {
            return self::STAGE_ACCEPTED;
        }

        if (filled($pendiente->user_id)) {
            return self::STAGE_ASSIGNED;
        }

        if (
            filled($pendiente->reviewed_by_user_id)
            || filled($pendiente->assigned_by_user_id)
            || filled($pendiente->review_notes)
        ) {
            return self::STAGE_ALFREDO_REVIEW;
        }

        return self::STAGE_POSTVENTA;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ServiceVisit::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(ServiceVisit::class)->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PendienteHistory::class)
            ->latest('changed_at')
            ->latest('id');
    }

    public function serviceVisitEvents(): HasMany
    {
        return $this->hasMany(ServiceVisitEvent::class);
    }

    public function unitAssignments(): HasMany
    {
        return $this->hasMany(ProductoUnidadAssignment::class)
            ->latest('id');
    }

    public function activeUnitAssignments(): HasMany
    {
        return $this->hasMany(ProductoUnidadAssignment::class)
            ->whereIn('status', ProductoUnidadAssignment::ACTIVE_STATUSES)
            ->latest('id');
    }

    public function scopeForFilamentListing(Builder $query): Builder
    {
        return $query
            ->with([
                'client:id,name,address,city,state,postal_code',
                'user:id,name',
                'reviewedBy:id,name',
                'assignedBy:id,name',
                'latestVisit' => fn ($latestVisit) => $latestVisit->select([
                    'service_visits.id',
                    'service_visits.pendiente_id',
                    'service_visits.arrival_photo',
                    'service_visits.departure_photo',
                    'service_visits.created_at',
                ]),
            ])
            ->withExists('visits');
    }

    public function scopeWithoutCompleted(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_COMPLETED);
    }

    public function scopeForHistoryPanel(Builder $query): Builder
    {
        return $query->with([
            'histories' => fn ($histories) => $histories
                ->select([
                    'id',
                    'pendiente_id',
                    'client_id',
                    'user_id',
                    'status_from',
                    'status_to',
                    'observation',
                    'changed_at',
                    'created_at',
                ])
                ->with('user:id,name'),
            'client' => fn ($client) => $client
                ->select(['id', 'name'])
                ->with([
                    'pendientes' => fn ($pendientes) => $pendientes
                        ->select([
                            'id',
                            'client_id',
                            'user_id',
                            'status',
                            'created_at',
                        ])
                        ->with('user:id,name'),
                ]),
        ]);
    }

    public function recordHistory(
        ?string $observation = null,
        ?string $statusFrom = null,
        ?string $statusTo = null
    ): void {
        app(\App\Services\PendienteHistoryService::class)->record(
            $this,
            $observation,
            $statusFrom,
            $statusTo,
        );
    }
}
