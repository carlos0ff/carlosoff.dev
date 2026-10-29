<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\StaffRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_admin' => 'boolean',
            'role' => StaffRole::class,
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Empresa do usuário do portal (nula para a equipe).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isStaff(): bool
    {
        return $this->is_admin && $this->role !== StaffRole::Client;
    }

    public function isPortalUser(): bool
    {
        return ! $this->is_admin && $this->role === StaffRole::Client && $this->client_id !== null;
    }

    public function hasRole(StaffRole ...$roles): bool
    {
        return $this->isStaff() && in_array($this->role, $roles, true);
    }
}
