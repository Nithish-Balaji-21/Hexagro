<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'initials', 'role', 'password_hash'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const UPDATED_AT = null;

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function configKey(): string
    {
        return match ($this->name) {
            'Jagadeesan' => 'jagadeesan',
            'Jagadeshwaran' => 'jagadeshwaran',
            'Vellingiri' => 'vellingiri',
            'Vikas' => 'vikas',
            default => strtolower(str_replace(' ', '', $this->name)),
        };
    }

    /**
     * @return HasMany<DebitTransaction, $this>
     */
    public function createdDebits(): HasMany
    {
        return $this->hasMany(DebitTransaction::class, 'created_by');
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'changed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
        ];
    }
}
