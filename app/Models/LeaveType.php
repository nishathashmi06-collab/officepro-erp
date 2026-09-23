<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = ['name', 'code', 'days_per_year', 'is_paid', 'requires_attachment', 'color', 'status'];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_attachment' => 'boolean',
    ];

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}
