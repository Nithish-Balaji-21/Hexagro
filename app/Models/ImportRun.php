<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

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

    /**
     * @return HasMany<DebitTransaction|CreditTransaction|Transfer, $this>
     */
    public function importedRecords(): HasMany
    {
        return match ($this->kind) {
            'debit' => $this->debitTransactions(),
            'credit' => $this->creditTransactions(),
            'transfers' => $this->transfers(),
            default => throw new InvalidArgumentException("Unsupported import kind: {$this->kind}"),
        };
    }

    public function importedRecordsWithRelations(): self
    {
        match ($this->kind) {
            'debit' => $this->load([
                'debitTransactions' => fn ($query) => $query
                    ->with(['costCenter', 'paidThrough'])
                    ->orderBy('txn_date')
                    ->orderBy('id'),
            ]),
            'credit' => $this->load([
                'creditTransactions' => fn ($query) => $query
                    ->with(['costCenter', 'receivedTo'])
                    ->orderBy('txn_date')
                    ->orderBy('id'),
            ]),
            'transfers' => $this->load([
                'transfers' => fn ($query) => $query
                    ->with(['costCenter', 'fromEntity', 'toEntity'])
                    ->orderBy('txn_date')
                    ->orderBy('id'),
            ]),
            default => throw new InvalidArgumentException("Unsupported import kind: {$this->kind}"),
        };

        return $this;
    }

    /**
     * @return Collection<int, DebitTransaction|CreditTransaction|Transfer>
     */
    public function detailRows(): Collection
    {
        return match ($this->kind) {
            'debit' => $this->debitTransactions,
            'credit' => $this->creditTransactions,
            'transfers' => $this->transfers,
            default => new Collection,
        };
    }
}
