<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin Builder
 * @property integer $id
 * @property string $password
 * @property string $name
 * @property string $email
 * @property integer $company_id
 * @property integer $partner_id
 * @property UserRole $role
 * @property boolean $is_active
 * @property boolean $must_change_password
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'partner_id',
        'role',
        'is_active',
        'must_change_password'
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
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'role' => UserRole::class
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Role-based permissions for this user's role and company.
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role', 'role')
            ->where('company_id', $this->company_id);
    }


    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    /**
     * Check if user has given permission name.
     *
     * Resolution order:
     * 1) User-specific permissions (user_permissions)
     * 2) Role-based permissions (role_permissions)
     * 3) Deny by default
     *
     * SUPERADMIN always returns true.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->company_id) {
            return false;
        }

        $companyId = $this->company_id;

        // 1) User-specific override
        $hasUserPermission = $this->userPermissions()
            ->where('company_id', $companyId)
            ->whereHas('permission', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();

        if ($hasUserPermission) {
            return true;
        }

        // 2) Role-based permission
        return RolePermission::query()
            ->where('company_id', $companyId)
            ->where('role', $this->role->value)
            ->whereHas('permission', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();
    }
}
