<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['laptop', 'desktop', 'monitor', 'mobile', 'printer', 'keyboard', 'mouse', 'office_furniture', 'networking', 'other'];

    public const STATUSES = ['available', 'assigned', 'maintenance', 'retired'];

    public const CONDITIONS = ['new', 'good', 'fair', 'poor', 'damaged'];

    protected $fillable = [
        'asset_code', 'name', 'category', 'serial_number', 'purchase_date', 'purchase_price',
        'condition', 'employee_id', 'location', 'status', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)->latest('assigned_at')->latest('id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class)->latest('started_at')->latest('id');
    }

    public static function nextCode(): string
    {
        $last = static::withTrashed()->where('asset_code', 'like', 'AST-%')->orderByDesc('id')->value('asset_code');
        $number = $last ? ((int) substr($last, 4)) + 1 : 1001;

        while (static::withTrashed()->where('asset_code', 'AST-'.$number)->exists()) {
            $number++;
        }

        return 'AST-'.$number;
    }
}
