<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutstandingBatch extends Model
{
    protected $fillable = [
        'kind',
        'batch_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OutstandingLine::class, 'batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalAmount(): string
    {
        return (string) $this->lines()->sum('amount');
    }
}
