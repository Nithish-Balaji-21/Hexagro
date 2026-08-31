<?php

namespace App\Models;

use App\Enums\DebitCategory;
use Database\Factories\DebitTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'txn_date',
    'cost_center_id',
    'category',
    'account',
    'paid_through_entity_id',
    'description',
    'amount',
    'created_by',
    'import_run_id',
    'updated_by',
])]
class DebitTransaction extends Model
{
    /** @use HasFactory<DebitTransactionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<CostCenter, $this>
     */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * @return BelongsTo<Entity, $this>
     */
    public function paidThrough(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'paid_through_entity_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
            'category' => DebitCategory::class,
            'amount' => 'decimal:2',
        ];
    }
}
