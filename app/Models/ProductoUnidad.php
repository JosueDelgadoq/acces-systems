<?php

namespace App\Models;

use App\Enums\Inventory\InventoryUnitCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use App\Support\Inventory\InventorySerialNormalizer;
use Illuminate\Support\Str;

class ProductoUnidad extends Model
{
    protected $table = 'producto_unidades';

    protected array $movementSnapshot = [];

    protected array $movementContext = [];

    public const STATUS_AVAILABLE = 'disponible';

    public const STATUS_RESERVED = 'reservado';

    public const STATUS_ASSIGNED = 'asignado';

    public const STATUS_INSTALLED = 'instalado';

    public const STATUS_SOLD = 'vendido';

    public const STATUS_REPAIR = 'reparacion';

    public const STATUS_RETIRED = 'baja';

    public const STATUS_OPTIONS = [
        self::STATUS_AVAILABLE => 'Disponible',
        self::STATUS_RESERVED => 'Reservado',
        self::STATUS_ASSIGNED => 'Asignado',
        self::STATUS_INSTALLED => 'Instalado',
        self::STATUS_SOLD => 'Vendido',
        self::STATUS_REPAIR => 'En reparacion',
        self::STATUS_RETIRED => 'Baja',
    ];

    public const CONDITION_OPTIONS = [
        'nuevo' => 'Nuevo',
        'usado' => 'Usado',
        'reacondicionado' => 'Reacondicionado',
        'repuestos' => 'Para repuestos',
        'danado' => 'Dañado',
    ];

    protected $fillable = [
        'producto_variante_id',
        'codigo_barra',
        'inventory_code',
        'serial_number',
        'serial_number_normalized',
        'estado',
        'condition',
        'location_id',
        'notes',
        'client_id',
        'client_equipo_id',
        'installed_at',
        'acquired_at',
        'source_import_batch_id',
        'source_sheet',
        'source_row_number',
        'source_row_hash',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'acquired_at' => 'datetime',
    ];

    public function variante(): BelongsTo
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function clientEquipo(): BelongsTo
    {
        return $this->belongsTo(ClientEquipo::class, 'client_equipo_id');
    }

    public function sourceImportBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryImportBatch::class, 'source_import_batch_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductoUnidadMovement::class)->latest();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProductoUnidadAssignment::class)->latest();
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(ProductoUnidadAssignment::class)
            ->whereIn('status', ProductoUnidadAssignment::ACTIVE_STATUSES)
            ->latestOfMany();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->estado] ?? Str::of((string) $this->estado)->replace('_', ' ')->title()->toString();
    }

    public function getInventoryLabelAttribute(): string
    {
        $variantLabel = $this->variante?->display_name;
        $identifier = $this->inventory_code ?: $this->codigo_barra;

        if ($variantLabel) {
            return $variantLabel . ' / ' . $identifier;
        }

        return $identifier ?: 'Unidad #' . $this->id;
    }

    public function getConditionLabelAttribute(): ?string
    {
        if (blank($this->condition)) {
            return null;
        }

        return self::CONDITION_OPTIONS[$this->condition] ?? (string) str($this->condition)->replace('_', ' ')->title();
    }

    public function getLocationLabelAttribute(): ?string
    {
        return $this->location?->name;
    }

    public static function normalizeSerial(?string $value): ?string
    {
        return InventorySerialNormalizer::normalize($value);
    }

    public function setMovementContext(array $context): self
    {
        $this->movementContext = $context;

        return $this;
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->estado ??= self::STATUS_AVAILABLE;
            $model->condition ??= $model->resolveDefaultCondition();
            $model->serial_number = filled($model->serial_number) ? trim((string) $model->serial_number) : null;
            $model->serial_number_normalized = self::normalizeSerial($model->serial_number);

            if (blank($model->inventory_code) && filled($model->codigo_barra)) {
                $model->inventory_code = $model->codigo_barra;
            }

            if (blank($model->codigo_barra) && filled($model->inventory_code)) {
                $model->codigo_barra = $model->inventory_code;
            }

            if (blank($model->inventory_code) && blank($model->codigo_barra)) {
                $generatedCode = $model->generateInventoryCode();
                $model->inventory_code = $generatedCode;
                $model->codigo_barra = $generatedCode;
            }
        });

        static::created(function (self $model): void {
            $context = $model->movementContext;

            $model->movements()->create([
                'producto_variante_id' => $model->producto_variante_id,
                'user_id' => $context['user_id'] ?? Auth::id(),
                'movement_type' => $context['movement_type'] ?? 'alta',
                'to_state' => $model->estado,
                'to_location_id' => $model->location_id,
                'reason' => $context['reason'] ?? 'ingreso_stock',
                'notes' => $context['notes'] ?? null,
                'reference_type' => $context['reference_type'] ?? null,
                'reference_id' => $context['reference_id'] ?? null,
                'metadata' => $context['metadata'] ?? null,
                'performed_at' => $context['performed_at'] ?? now(),
            ]);

            $model->movementContext = [];
        });

        static::updating(function (self $model): void {
            $model->movementSnapshot = [
                'estado' => $model->getOriginal('estado'),
                'location_id' => $model->getOriginal('location_id'),
            ];
        });

        static::updated(function (self $model): void {
            $stateChanged = $model->wasChanged('estado');
            $locationChanged = $model->wasChanged('location_id');

            if (! $stateChanged && ! $locationChanged) {
                $model->movementContext = [];

                return;
            }

            $context = $model->movementContext;

            $model->movements()->create([
                'producto_variante_id' => $model->producto_variante_id,
                'user_id' => $context['user_id'] ?? Auth::id(),
                'movement_type' => $context['movement_type'] ?? $model->resolveAutomaticMovementType($stateChanged, $locationChanged),
                'from_state' => $stateChanged ? ($model->movementSnapshot['estado'] ?? null) : $model->estado,
                'to_state' => $model->estado,
                'from_location_id' => $locationChanged ? ($model->movementSnapshot['location_id'] ?? null) : $model->location_id,
                'to_location_id' => $model->location_id,
                'reason' => $context['reason'] ?? 'actualizacion_estado',
                'notes' => $context['notes'] ?? null,
                'reference_type' => $context['reference_type'] ?? null,
                'reference_id' => $context['reference_id'] ?? null,
                'metadata' => $context['metadata'] ?? null,
                'performed_at' => $context['performed_at'] ?? now(),
            ]);

            $model->movementContext = [];
        });
    }

    protected function resolveDefaultCondition(): string
    {
        if (filled($this->condition)) {
            return (string) $this->condition;
        }

        $variantState = $this->variante?->estado;

        if (filled($variantState) && array_key_exists($variantState, self::CONDITION_OPTIONS)) {
            return (string) $variantState;
        }

        return InventoryUnitCondition::NEW->value;
    }

    protected function resolveAutomaticMovementType(bool $stateChanged, bool $locationChanged): string
    {
        if ($locationChanged && ! $stateChanged) {
            return 'reubicacion';
        }

        return 'cambio_estado';
    }

    protected function generateInventoryCode(): string
    {
        do {
            $code = 'INV-' . Str::upper(Str::random(10));
        } while (self::query()
            ->where('inventory_code', $code)
            ->orWhere('codigo_barra', $code)
            ->exists());

        return $code;
    }

    protected function generateBarcode(): string
    {
        return $this->generateInventoryCode();
    }
}
