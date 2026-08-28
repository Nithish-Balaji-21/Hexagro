<?php

namespace App\Models;

use Database\Factories\HistoricalAlamExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['txn_date', 'account', 'description', 'amount', 'created_by'])]
class HistoricalAlamExpense extends Model
{
    /** @use HasFactory<HistoricalAlamExpenseFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'txn_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
