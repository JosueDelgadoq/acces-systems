<?php

namespace App\Models;

use App\Support\TextEncodingNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    public const NORMALIZABLE_TEXT_ATTRIBUTES = [
        'name',
        'company',
        'address',
        'city',
        'state',
        'postal_code',
        'address_notes',
        'notes',
        'contacto',
        'mails',
        'direccion_normalizada',
    ];

    protected $fillable = [
        'id',
        'name',
        'company',
        'email',
        'cuil',
        'phone',
        'id_crm',
        'installation_date',
        'notes',
        'address',
        'contacto',
        'mails',
        'city',
        'state',
        'postal_code',
        'address_notes',
        'latitud',
        'longitud',
        'direccion_normalizada',
    ];

    protected static function booted()
    {
        static::saving(function (Client $client): void {
            foreach (self::NORMALIZABLE_TEXT_ATTRIBUTES as $attribute) {
                $value = $client->getAttribute($attribute);

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $client->setAttribute($attribute, TextEncodingNormalizer::normalize($value));
            }

            if ($client->exists) {
                // borrar equipos antes de volver a guardar
                // $client->equipos()->delete();
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function pendientes()
    {
        return $this->hasMany(Pendiente::class);
    }

    public function conservations()
    {
        return $this->hasMany(Conservation::class);
    }

    public function equipos()
    {
        return $this->hasMany(ClientEquipo::class);
    }

    public function habilitations()
    {
        return $this->hasMany(Habilitation::class);
    }

    public function technicalBudgets()
    {
        return $this->hasMany(TechnicalBudget::class);
    }

    public function equipmentDeliveries()
    {
        return $this->hasMany(EquipmentDelivery::class);
    }
    public function billingControls()
    {
        return $this->hasMany(BillingControl::class);
    }

    public function samuEvents(): HasMany
    {
        return $this->hasMany(SamuEvent::class);
    }

    /**
     * Get full address as a single string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);

        return implode(', ', $parts) ?: 'Sin dirección';
    }
}
