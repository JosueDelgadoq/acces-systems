<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

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
        'cliente_envio_fotos',
        'cliente_envio_planos',
        'requiere_relevamiento_pago',
        'orientacion_dada',
        'tipo_orientacion',
        'fecha_orientacion',
        'estado_pipeline',
        'resultado_final',
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

    /*
    |--------------------------------------------------------------------------
    | AUTO GENERAR ID CRM
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {

            if (empty($lead->crm_id)) {

                $yearMonth = now()->format('Ymd');

                $count = static::where('crm_id', 'LIKE', $yearMonth . '%')->count();

                $lead->crm_id = $yearMonth . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            }

            if (empty($lead->fecha_ingreso)) {
                $lead->fecha_ingreso = now();
            }

            if (empty($lead->hora_ingreso)) {
                $lead->hora_ingreso = now();
            }
        });
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
}