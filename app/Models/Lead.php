<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class Lead extends Model
{
    use HasFactory;

    public const STAGE_INGRESADO = 'Ingresado';
    public const STAGE_CONTACTADO = 'Contactado';
    public const STAGE_ORIENTACION = 'Orientacion dada';
    public const STAGE_COTIZACION = 'Cotizacion enviada';
    public const STAGE_PRESUPUESTO = 'Presupuesto definitivo enviado';
    public const STAGE_VENTA_CERRADA = 'Venta cerrada';
    public const STAGE_PERDIDO = 'Perdido';
    public const STAGE_POSTERGADO = 'Postergado';

    public const RESULTADO_ABIERTO = 'Abierto';
    public const RESULTADO_VENDIDO = 'Vendido';
    public const RESULTADO_PERDIDO = 'Perdido';

    public const PIPELINE_OPTIONS = [
        self::STAGE_INGRESADO => 'Ingresado',
        self::STAGE_CONTACTADO => 'Contactado',
        self::STAGE_ORIENTACION => 'Orientacion dada',
        self::STAGE_COTIZACION => 'Cotizacion enviada',
        self::STAGE_PRESUPUESTO => 'Presupuesto definitivo enviado',
        self::STAGE_VENTA_CERRADA => 'Venta cerrada',
        self::STAGE_PERDIDO => 'Perdido',
        self::STAGE_POSTERGADO => 'Postergado',
    ];

    public const OPEN_PIPELINE_OPTIONS = [
        self::STAGE_INGRESADO => 'Ingresado',
        self::STAGE_CONTACTADO => 'Contactado',
        self::STAGE_ORIENTACION => 'Orientacion dada',
        self::STAGE_COTIZACION => 'Cotizacion enviada',
        self::STAGE_PRESUPUESTO => 'Presupuesto definitivo enviado',
        self::STAGE_POSTERGADO => 'Postergado',
    ];

    public const CLOSED_PIPELINES = [
        self::STAGE_VENTA_CERRADA,
        self::STAGE_PERDIDO,
    ];

    public const RESULTADO_OPTIONS = [
        self::RESULTADO_ABIERTO => 'Abierto',
        self::RESULTADO_VENDIDO => 'Vendido',
        self::RESULTADO_PERDIDO => 'Perdido',
    ];

    public const CANAL_ORIGEN_OPTIONS = [
        'whatsapp' => 'WhatsApp',
        'redes_sociales' => 'Redes sociales',
        'mail' => 'Mail',
        'telefono' => 'Telefono',
    ];

    public const TIPO_CLIENTE_OPTIONS = [
        'Residencial' => 'Residencial',
        'Público' => 'Público',
        'Empresa' => 'Empresa',
        'Constructor' => 'Constructor',
    ];

    public const SUBTIPO_PUBLICO_OPTIONS = [
        'Universidad' => 'Universidad',
        'Municipalidad' => 'Municipalidad',
        'Banco' => 'Banco',
        'Hospital' => 'Hospital',
        'Otro' => 'Otro',
    ];

    public const TIPO_INSTALACION_OPTIONS = [
        'Recta' => 'Recta',
        'Curva' => 'Curva',
        'Exterior' => 'Exterior',
        'Piscina' => 'Piscina',
        'Vertical' => 'Vertical',
    ];

    public const TIPO_ORIENTACION_OPTIONS = [
        'Verbal telefónica' => 'Verbal telefónica',
        'Audio WhatsApp' => 'Audio WhatsApp',
        'Texto WhatsApp' => 'Texto WhatsApp',
        'Estimado estructurado WhatsApp' => 'Estimado estructurado WhatsApp',
        'PDF enviado por WhatsApp' => 'PDF enviado por WhatsApp',
        'PDF enviado por Mail' => 'PDF enviado por Mail',
    ];

    public const MOTIVO_PERDIDA_OPTIONS = [
        'Precio' => 'Precio alto',
        'Forma de pago' => 'Forma de pago',
        'Tiempo entrega' => 'Tiempo de entrega',
        'Competencia' => 'Competencia',
        'Calidad percibida' => 'Calidad percibida',
        'Falta decisión' => 'Falta de decisión',
        'Otro' => 'Otro',
    ];

    public const AREA_RESPONSABLE_OPTIONS = [
        'Comercial Venta' => 'Comercial Venta',
        'Postventa' => 'Postventa',
    ];

    protected $fillable = [
        'crm_id',
        'fecha_ingreso',
        'hora_ingreso',
        'comercial_asignado_id',
        'canal_origen',
        'nombre',
        'apellido',
        'telefono',
        'email',
        'localidad',
        'provincia',
        'zona_comercial',
        'tipo_cliente',
        'subtipo_publico',
        'producto_interes',
        'tipo_instalacion',
        'documentacion_cliente',
        'documentacion_cliente_archivos',
        'cliente_envio_fotos',
        'cliente_envio_planos',
        'requiere_relevamiento_pago',
        'orientacion_dada',
        'tipo_orientacion',
        'fecha_orientacion',
        'estado_pipeline',
        'resultado_final',
        'estado',
        'motivo_perdida',
        'fecha_cierre',
        'area_responsable',
        'created_by',
        'fecha_ultimo_seguimiento',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'hora_ingreso' => 'datetime:H:i',
        'fecha_orientacion' => 'date',
        'fecha_cierre' => 'date',
        'fecha_ultimo_seguimiento' => 'date',
        'documentacion_cliente_archivos' => 'array',
        'cliente_envio_fotos' => 'boolean',
        'cliente_envio_planos' => 'boolean',
        'requiere_relevamiento_pago' => 'boolean',
        'orientacion_dada' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES PARA DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function scopeUrgente($query)
    {
        return $query->whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(5));
    }

    public function scopeAtencion($query)
    {
        return $query->whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(3));
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('fecha_ingreso', now()->month);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->canAccess('lead.view_all') || $user->canAccess('lead.assign')) {
            return $query;
        }

        return $query->where(function (Builder $visibleQuery) use ($user): void {
            $visibleQuery
                ->where('created_by', $user->id)
                ->orWhere('comercial_asignado_id', $user->id);
        });
    }

    public function scopeOpenPipeline(Builder $query): Builder
    {
        return $query->whereNotIn('estado_pipeline', self::CLOSED_PIPELINES);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO GENERAR ID CRM
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {
            // SOLO si vos no lo cargaste
            if (empty($lead->crm_id)) {
                try {
                    Cache::lock('lead-crm-id:' . now()->format('Ymd'), 5)->block(5, function () use ($lead): void {
                        $lead->crm_id = static::nextCrmId();
                    });
                } catch (LockTimeoutException) {
                    $lead->crm_id = static::nextCrmId();
                }
            }

            if (empty($lead->fecha_ingreso)) {
                $lead->fecha_ingreso = now();
            }

            if (empty($lead->hora_ingreso)) {
                $lead->hora_ingreso = now();
            }
        });
    }

    protected static function nextCrmId(): string
    {
        $prefix = now()->format('Ymd');

        $lastCrmId = static::query()
            ->where('crm_id', 'like', $prefix . '-%')
            ->orderByDesc('crm_id')
            ->value('crm_id');

        $nextNumber = 1;

        if (is_string($lastCrmId) && preg_match('/^\d{8}-(\d{4})$/', $lastCrmId, $matches) === 1) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = $prefix . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (static::query()->where('crm_id', $candidate)->exists());

        return $candidate;
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function comercialAsignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comercial_asignado_id');
    }

    public function presupuestos(): HasMany
    {
        return $this->hasMany(Presupuesto::class);
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(Seguimiento::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LeadHistory::class)
            ->latest('changed_at')
            ->latest('id');
    }

    public function latestSeguimiento(): HasOne
    {
        return $this->hasOne(Seguimiento::class)
            ->latestOfMany('created_at');
    }

    public function nextPendingSeguimiento(): HasOne
    {
        return $this->hasOne(Seguimiento::class)
            ->where('estado', Seguimiento::STATUS_PENDIENTE)
            ->oldestOfMany('fecha_proxima_accion');
    }

    public function postventas(): HasMany
    {
        return $this->hasMany(Postventa::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function samuEvents(): HasMany
    {
        return $this->hasMany(SamuEvent::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ATRIBUTOS CALCULADOS (CRM)
    |--------------------------------------------------------------------------
    */

    public function getDiasSinSeguimientoAttribute()
    {
        if (!$this->fecha_ultimo_seguimiento) {
            return 0;
        }

        return now()->diffInDays($this->fecha_ultimo_seguimiento);
    }

    public function getEstadoSemaforoAttribute()
    {
        $dias = $this->dias_sin_seguimiento;

        if ($dias > 5) {
            return 'urgente';
        }

        if ($dias > 3) {
            return 'atencion';
        }

        return 'ok';
    }

    public function getClienteAttribute()
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    public static function getPipelineOptions(): array
    {
        return self::PIPELINE_OPTIONS;
    }

    public static function getOpenPipelineOptions(): array
    {
        return self::OPEN_PIPELINE_OPTIONS;
    }

    public static function getResultadoOptions(): array
    {
        return self::RESULTADO_OPTIONS;
    }

    public static function getCanalOrigenOptions(): array
    {
        return self::CANAL_ORIGEN_OPTIONS;
    }

    public static function getTipoClienteOptions(): array
    {
        return self::TIPO_CLIENTE_OPTIONS;
    }

    public static function getSubtipoPublicoOptions(): array
    {
        return self::SUBTIPO_PUBLICO_OPTIONS;
    }

    public static function getTipoInstalacionOptions(): array
    {
        return self::TIPO_INSTALACION_OPTIONS;
    }

    public static function getTipoOrientacionOptions(): array
    {
        return self::TIPO_ORIENTACION_OPTIONS;
    }

    public static function getMotivoPerdidaOptions(): array
    {
        return self::MOTIVO_PERDIDA_OPTIONS;
    }

    public static function getAreaResponsableOptions(): array
    {
        return self::AREA_RESPONSABLE_OPTIONS;
    }

    public static function getPipelineColor(?string $stage): string
    {
        return match ($stage) {
            self::STAGE_INGRESADO => 'gray',
            self::STAGE_CONTACTADO => 'info',
            self::STAGE_ORIENTACION => 'primary',
            self::STAGE_COTIZACION => 'warning',
            self::STAGE_PRESUPUESTO => 'warning',
            self::STAGE_VENTA_CERRADA => 'success',
            self::STAGE_PERDIDO => 'danger',
            self::STAGE_POSTERGADO => 'gray',
            default => 'gray',
        };
    }

    public static function getPipelineLabel(?string $stage): string
    {
        return self::PIPELINE_OPTIONS[$stage]
            ?? (string) str($stage)->replace('_', ' ')->title();
    }
}
