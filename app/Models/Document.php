<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['employee_document', 'contract', 'id_document', 'certificate', 'company_document', 'policy'];

    protected $fillable = [
        'title', 'category', 'file_path', 'original_name', 'mime_type', 'file_size',
        'employee_id', 'expiry_date', 'employee_visible', 'description', 'uploaded_by', 'expiry_notified_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'employee_visible' => 'boolean',
        'expiry_notified_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Users without documents.view_all only see their own visible documents
     * plus visible company-wide documents (policies etc.).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('documents.view_all')) {
            return $query;
        }

        $employeeId = $user->employee?->id ?? 0;

        return $query->where('documents.employee_visible', true)
            ->where(function (Builder $q) use ($employeeId) {
                $q->where('documents.employee_id', $employeeId)->orWhereNull('documents.employee_id');
            });
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString());
    }

    public function expiryState(): ?string
    {
        if (! $this->expiry_date) {
            return null;
        }

        if ($this->expiry_date->isPast() && ! $this->expiry_date->isToday()) {
            return 'expired';
        }

        return $this->expiry_date->lte(now()->addDays((int) setting('document_expiry_days', 30))) ? 'expiring' : 'valid';
    }

    public function getReadableSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }
}
