<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'display_name',
    'email',
    'role',
    'photo_url',
    'country',
    'organization',
    'telephone',
    'firebase_uid',
    'email_verified_at',
    'password',
    'preferences',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_CLERK = 'clerk';

    /** Roles allowed to approve / reject submissions. */
    public const APPROVER_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
    ];

    /** Roles allowed to upload / manage documents in the private library. */
    public const DOCUMENT_MANAGER_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
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
            'preferences' => 'array',
        ];
    }

    /**
     * Whether the user can upload / manage documents in the private library.
     */
    public function canManageDocuments(): bool
    {
        return in_array($this->role, self::DOCUMENT_MANAGER_ROLES, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isClerk(): bool
    {
        return $this->role === self::ROLE_CLERK;
    }

    /** Whether the user can approve or reject submissions. */
    public function canApprove(): bool
    {
        return in_array($this->role, self::APPROVER_ROLES, true);
    }

    /** Whether the user can manage other users (admin-only). */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }
}
