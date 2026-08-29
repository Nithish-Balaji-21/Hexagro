<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'entity_id',
        'txn_date',
        'cost_center_id',
        'particulars',
        'signed_amount',
        'debit',
        'credit',
        'source_table',
        'source_id',
    ];

    /**
     * @return BelongsTo<Entity, $this>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * @return BelongsTo<CostCenter, $this>
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
            'signed_amount' => 'decimal:2',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }
}
