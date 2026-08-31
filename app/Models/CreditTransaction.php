<?php

namespace App\Models;

use App\Enums\CreditType;
use Database\Factories\CreditTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'txn_date',
    'cost_center_id',
    'credit_type',
    'received_to_entity_id',
    'description',
    'amount',
    'created_by',
    'import_run_id',
    'updated_by',
])]
class CreditTransaction extends Model
{
    /** @use HasFactory<CreditTransactionFactory> */
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
    public function receivedTo(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'received_to_entity_id');
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
            'credit_type' => CreditType::class,
            'amount' => 'decimal:2',
        ];
    }
}
