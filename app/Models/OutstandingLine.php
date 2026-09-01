<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutstandingLine extends Model
{
    protected $fillable = [
        'batch_id',
        'cost_center_id',
        'party_name',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OutstandingBatch::class, 'batch_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
