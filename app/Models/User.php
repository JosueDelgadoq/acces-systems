<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Temporarily allow all users for testing
        return true;
    }

public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isTecnico(): bool
{
    return $this->role === 'tecnico';
}

public function isManager(): bool
{
    return $this->role === 'Manager';
}
    /**
     * Get the role label in Spanish
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrador',
            'tecnico' => 'Técnico',
            'Manager' => 'Postventa',
            default => 'Sin rol',
        };
    }

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TECNICO = 'tecnico';

    public const ROLE_MANAGER = 'Manager';

public const ROLES = [
    'admin' => 'Administrador',
    'tecnico' => 'Técnico',
    'Manager' => 'Postventa',
];
}
