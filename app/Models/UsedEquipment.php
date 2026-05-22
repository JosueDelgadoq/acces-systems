<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UsedEquipment extends Model
{
    protected array $movementSnapshot = [];

    protected $fillable = [
        'code',
        'type',
        'brand',
        'status',
        'ingress_condition',
        'warehouse_zone',
        'location',
        'notes',
        'missing_parts',
        'photos',
    ];

    protected $casts = [
        'missing_parts' => 'array',
        'photos' => 'array',
    ];

    public const TYPE_OPTIONS = [
        'silla' => 'Silla',
        'motor' => 'Motor',
        'plataforma' => 'Plataforma',
    ];

    public const BRAND_OPTIONS = [
        'acorn' => 'Acorn',
        'bespoke' => 'Bespoke',
        'meditek' => 'Meditek',
        'vimec' => 'Vimec',
        'k2' => 'K2',
        'otra' => 'Otra',
    ];

    public const STATUS_OPTIONS = [
        'completa' => 'Completa',
        'a_revisar' => 'A revisar',
        'repuestos' => 'Repuestos',
        'vendida' => 'Vendida',
    ];

    public const INGRESS_CONDITION_OPTIONS = [
        'CF' => 'CF - Completa funcional',
        'CI' => 'CI - Completa incompleta',
        'IN' => 'IN - Incompleta no funcional',
        'RP' => 'RP - Repuestos',
    ];

    public const WAREHOUSE_ZONE_OPTIONS = [
        'verde' => 'Zona verde',
        'amarilla' => 'Zona amarilla',
        'roja' => 'Zona roja',
    ];

    public const MISSING_PART_OPTIONS = [
        'sin_placa' => 'Sin placa',
        'sin_baterias' => 'Sin baterias',
        'sin_controles' => 'Sin controles',
        'sin_tapa' => 'Sin tapa de bateria',
        'sin_resorte' => 'Sin resorte',
        'sin_tetones' => 'Sin tetones de carga',
        'sin_apoya_pies' => 'Sin apoyapies',
        'sin_soporte_cuello' => 'Sin soporte/cuello',
        'joystick' => 'Joystick roto',
        'cinturon' => 'Cinturon faltante',
    ];

    public function movements()
    {
        return $this->hasMany(UsedEquipmentMovement::class)->latest();
    }

    public function getConditionAttribute()
    {
        return empty($this->missing_parts) ? 'complete' : 'incomplete';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function getWarehouseZoneLabelAttribute(): ?string
    {
        return $this->warehouse_zone ? (self::WAREHOUSE_ZONE_OPTIONS[$this->warehouse_zone] ?? $this->warehouse_zone) : null;
    }

    public function getIngressConditionLabelAttribute(): ?string
    {
        return $this->ingress_condition ? (self::INGRESS_CONDITION_OPTIONS[$this->ingress_condition] ?? $this->ingress_condition) : null;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->type] ?? $this->type;
    }

    public function getBrandLabelAttribute(): ?string
    {
        return $this->brand ? (self::BRAND_OPTIONS[$this->brand] ?? ucfirst($this->brand)) : null;
    }

    public function getMissingPartsLabelsAttribute(): array
    {
        return collect($this->missing_parts ?? [])
            ->map(fn (string $part) => self::MISSING_PART_OPTIONS[$part] ?? $part)
            ->values()
            ->all();
    }

    public function getFullLocationAttribute(): ?string
    {
        $parts = array_filter([$this->warehouse_zone_label, $this->location]);

        return $parts === [] ? null : implode(' | ', $parts);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('used-equipment.show', $this);
    }

    public function getLabelUrlAttribute(): string
    {
        return route('used-equipment.label', $this);
    }

    public function recordMovement(string $type, array $payload = []): void
    {
        $this->movements()->create([
            'user_id' => Auth::id(),
            'movement_type' => $type,
            'description' => $payload['description'] ?? null,
            'from_status' => $payload['from_status'] ?? null,
            'to_status' => $payload['to_status'] ?? null,
            'from_location' => $payload['from_location'] ?? null,
            'to_location' => $payload['to_location'] ?? null,
            'parts_before' => $payload['parts_before'] ?? null,
            'parts_after' => $payload['parts_after'] ?? null,
            'meta' => $payload['meta'] ?? null,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->code) {
                $model->code = $model->generateCode();
            }
        });

        static::created(function (self $model) {
            $model->recordMovement('ingreso', [
                'description' => 'Ingreso inicial al stock de usados.',
                'to_status' => $model->status,
                'to_location' => $model->full_location,
                'parts_after' => $model->missing_parts,
            ]);
        });

        static::updating(function (self $model) {
            $model->movementSnapshot = [
                'status' => $model->getOriginal('status'),
                'warehouse_zone' => $model->getOriginal('warehouse_zone'),
                'location' => $model->getOriginal('location'),
                'missing_parts' => self::normalizePartsSnapshot($model->getOriginal('missing_parts')),
            ];
        });

        static::updated(function (self $model) {
            $before = $model->movementSnapshot;

            if ($model->wasChanged('status')) {
                $model->recordMovement('cambio_estado', [
                    'description' => 'Cambio de estado del equipo.',
                    'from_status' => $before['status'] ?? null,
                    'to_status' => $model->status,
                ]);
            }

            if ($model->wasChanged(['warehouse_zone', 'location'])) {
                $model->recordMovement('reubicacion', [
                    'description' => 'Actualizacion de ubicacion en deposito.',
                    'from_location' => self::composeLocation($before['warehouse_zone'] ?? null, $before['location'] ?? null),
                    'to_location' => $model->full_location,
                ]);
            }

            if ($model->wasChanged('missing_parts')) {
                $model->recordMovement('actualizacion_componentes', [
                    'description' => 'Actualizacion de faltantes o piezas registradas.',
                    'parts_before' => $before['missing_parts'] ?? [],
                    'parts_after' => $model->missing_parts ?? [],
                ]);
            }
        });
    }

    protected function generateCode(): string
    {
        if ($this->type === 'silla') {
            $brandPrefix = strtoupper(substr($this->brand ?? 'XX', 0, 2));
            $last = self::query()
                ->where('type', 'silla')
                ->where('brand', $this->brand)
                ->where('code', 'like', 'SU-' . $brandPrefix . '-%')
                ->latest('id')
                ->first();

            $number = $this->nextSequenceNumber($last?->code, 4);

            return 'SU-' . $brandPrefix . '-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        }

        $prefix = match ($this->type) {
            'motor' => 'M',
            'plataforma' => 'P',
            default => 'X',
        };

        $last = self::query()
            ->where('type', $this->type)
            ->where('code', 'like', $prefix . '-%')
            ->latest('id')
            ->first();

        $number = $this->nextSequenceNumber($last?->code, 3);

        return $prefix . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    protected function nextSequenceNumber(?string $lastCode, int $minLength): int
    {
        if ($lastCode && preg_match('/(\d{' . $minLength . ',})$/', $lastCode, $matches)) {
            return ((int) $matches[1]) + 1;
        }

        return 1;
    }

    protected static function composeLocation(?string $zone, ?string $location): ?string
    {
        $zoneLabel = $zone ? (self::WAREHOUSE_ZONE_OPTIONS[$zone] ?? $zone) : null;
        $parts = array_filter([$zoneLabel, $location]);

        return $parts === [] ? null : implode(' | ', $parts);
    }

    protected static function normalizePartsSnapshot(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : Arr::wrap($value);
        }

        return Arr::wrap($value);
    }
}
