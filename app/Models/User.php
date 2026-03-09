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

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is technician
     */
    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    /**
     * Check if user is commercial
     */
    public function isCommercial(): bool
    {
        return $this->role === 'commercial';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Get the role label in Spanish
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrador',
            'manager' => 'Gerente',
            'technician' => 'Técnico',
            'commercial' => 'Comercial',
            'client' => 'Cliente',
            default => 'Sin rol',
        };
    }

    /**
     * Role constants
     */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_TECHNICIAN = 'technician';
    public const ROLE_COMMERCIAL = 'commercial';
    public const ROLE_CLIENT = 'client';

    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_MANAGER => 'Gerente',
        self::ROLE_TECHNICIAN => 'Técnico',
        self::ROLE_COMMERCIAL => 'Comercial',
        self::ROLE_CLIENT => 'Cliente',
    ];
}
