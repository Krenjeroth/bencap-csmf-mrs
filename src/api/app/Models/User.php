<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A staff account. Primary key is a UUIDv7 (HasUuids), per ADR 0003.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property bool $must_change_password
 * @property bool $is_active
 * @property Carbon|null $two_factor_confirmed_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'is_active',
        'office_id',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** Mirrors the column defaults so new instances are never null. */
    protected $attributes = [
        'must_change_password' => true,
        'is_active' => true,
    ];

    /** Permission titles for this request, memoised by permissionTitles(). */
    private ?Collection $permissionTitleCache = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Office, $this> */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * The office this account's data is limited to, or null for no limit.
     * System Administrators see every office (ADR 0004); other users with an
     * assigned office see only that office's records.
     */
    public function scopedOfficeId(): ?int
    {
        return $this->isSystemAdministrator() ? null : $this->office_id;
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isSystemAdministrator(): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->is_system);
    }

    public function hasRole(string $title): bool
    {
        return $this->roles->contains('title', $title);
    }

    /**
     * All permission titles granted through the user's roles.
     *
     * @return Collection<int, string>
     */
    public function permissionTitles(): Collection
    {
        if ($this->permissionTitleCache === null) {
            $this->loadMissing('roles.permissions');
            $this->permissionTitleCache = $this->roles
                ->flatMap(fn (Role $role) => $role->permissions->pluck('title'))
                ->unique()
                ->sort()
                ->values();
        }

        return $this->permissionTitleCache;
    }

    public function hasPermission(string $title): bool
    {
        return $this->isSystemAdministrator() || $this->permissionTitles()->contains($title);
    }

    /** Clears memoised roles/permissions after roles are changed. */
    public function forgetPermissionCache(): void
    {
        $this->permissionTitleCache = null;
        $this->unsetRelation('roles');
    }

    public function hasEnabledTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /** System Administrators must enable two-factor login (ADR 0004). */
    public function requiresTwoFactor(): bool
    {
        return $this->isSystemAdministrator();
    }
}
