<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRun extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    public const KINDS = ['debit', 'credit', 'transfers'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kind',
        'filename',
        'user_id',
        'row_count',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<DebitTransaction, $this>
     */
    public function debitTransactions(): HasMany
    {
        return $this->hasMany(DebitTransaction::class);
    }

    /**
     * @return HasMany<CreditTransaction, $this>
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * @return HasMany<Transfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(Transfer::class);
    }
}
