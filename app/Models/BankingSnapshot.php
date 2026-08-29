<?php

namespace App\Models;

use Database\Factories\BankingSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'as_of_date',
    'cc_limit',
    'cc_utilised',
    'current_balance',
    'term_loan',
    'tl_limit',
    'alam_utilised',
    'created_by',
])]
class BankingSnapshot extends Model
{
    /** @use HasFactory<BankingSnapshotFactory> */
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
            'as_of_date' => 'date',
            'cc_limit' => 'decimal:2',
            'cc_utilised' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'term_loan' => 'decimal:2',
            'tl_limit' => 'decimal:2',
            'alam_utilised' => 'decimal:2',
        ];
    }
}
