<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const STATUSES = ['active', 'disabled'];

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'phone', 'profile_photo',
        'status', 'last_login_at', 'last_login_ip', 'preferences',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** @var array<int, string>|null */
    private ?array $permissionCache = null;

    private ?array $teamIdsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role?->slug, $slugs, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Permissions::ROLE_SUPER_ADMIN);
    }

    /** Admin-level roles may only be managed by a super admin. */
    public function isPrivileged(): bool
    {
        return $this->hasRole(Permissions::ROLE_SUPER_ADMIN, Permissions::ROLE_ADMIN);
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->permissionCache === null) {
            $this->permissionCache = $this->role
                ? $this->role->permissions()->pluck('slug')->all()
                : [];
        }

        return in_array($slug, $this->permissionCache, true);
    }

    /**
     * IDs of employees this user may see as "team": employees in departments
     * the user's employee record manages, plus the user themself.
     *
     * @return array<int, int>
     */
    public function teamEmployeeIds(): array
    {
        if ($this->teamIdsCache !== null) {
            return $this->teamIdsCache;
        }

        $employee = $this->employee;
        if (! $employee) {
            return $this->teamIdsCache = [];
        }

        $departmentIds = Department::where('manager_id', $employee->id)->pluck('id');

        return $this->teamIdsCache = Employee::whereIn('department_id', $departmentIds)
            ->pluck('id')
            ->push($employee->id)
            ->unique()
            ->values()
            ->all();
    }

    public function preference(string $key, mixed $default = null): mixed
    {
        return data_get($this->preferences ?? [], $key, $default);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $photo = $this->profile_photo ?: $this->employee?->profile_photo;

        return $photo ? Storage::disk('public')->url($photo) : null;
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr(end($parts) ?: '', 0, 1));
    }
}
