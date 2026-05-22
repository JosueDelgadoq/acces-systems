<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Traits\HasRoles;
use App\Models\ServiceVisit;
use Laravel\Sanctum\HasApiTokens;
use App\Models\TechnicianLocation;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use HasApiTokens;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_lat',
        'last_lng',
        'last_seen_at',
        'tracking_enabled',
        'technician_status',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'tracking_enabled' => 'boolean',
            'last_lat' => 'float',
            'last_lng' => 'float',
        ];
    }

    protected static function booted(): void
    {
        Gate::before(function ($user) {
            if ($user->hasRole('admin')) {
                return true;
            }

            return null;
        });
    }

    public function tecnico()
    {
        return $this->hasOne(Tecnico::class);
    }

   public function technicianLocations()
{
    return $this->hasMany(\App\Models\TechnicianLocation::class);
}

    public function latestTechnicianLocation(): HasOne
    {
        return $this->hasOne(TechnicianLocation::class)->latestOfMany('tracked_at');
    }

    public function serviceVisits(): HasMany
    {
        return $this->hasMany(ServiceVisit::class, 'technician_id');
    }

    public function activeServiceVisit(): HasOne
    {
        return $this->hasOne(ServiceVisit::class, 'technician_id')
            ->active()
            ->latestOfMany();
    }

    public function serviceVisitEvents(): HasMany
    {
        return $this->hasMany(ServiceVisitEvent::class);
    }

    public function productoUnidadAssignments(): HasMany
    {
        return $this->hasMany(ProductoUnidadAssignment::class, 'technician_id');
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->roles()->exists() || $this->permissions()->exists();
    }

    public function canAccess(string $permission): bool
    {
        return filled($permission) && $this->can($permission);
    }
}
