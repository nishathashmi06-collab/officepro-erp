<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use SoftDeletes;

    public const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'contract', 'intern'];

    public const STATUSES = ['active', 'on_leave', 'inactive', 'resigned'];

    public const GENDERS = ['male', 'female', 'other'];

    protected $fillable = [
        'user_id', 'employee_code', 'first_name', 'last_name', 'email', 'phone', 'gender',
        'date_of_birth', 'address', 'emergency_contact_name', 'emergency_contact_phone',
        'department_id', 'designation_id', 'joining_date', 'employment_type', 'salary',
        'status', 'profile_photo', 'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Restrict a query to employees the given user may see.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('employees.view_all')) {
            return $query;
        }

        if ($user->hasPermission('employees.view_team')) {
            return $query->whereIn('employees.id', $user->teamEmployeeIds());
        }

        return $query->where('employees.id', $user->employee?->id ?? 0);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%'.$term.'%';
            $q->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('employee_code', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]);
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->profile_photo ? Storage::disk('public')->url($this->profile_photo) : null;
    }

    public static function nextCode(): string
    {
        $last = static::withTrashed()->where('employee_code', 'like', 'EMP-%')->orderByDesc('id')->value('employee_code');
        $number = $last ? ((int) substr($last, 4)) + 1 : 1001;

        while (static::withTrashed()->where('employee_code', 'EMP-'.$number)->exists()) {
            $number++;
        }

        return 'EMP-'.$number;
    }
}
