<?php

namespace App\Models;

use Database\Factories\CostCenterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class CostCenter extends Model
{
    /** @use HasFactory<CostCenterFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return HasMany<ShareholderShare, $this>
     */
    public function shareholderShares(): HasMany
    {
        return $this->hasMany(ShareholderShare::class);
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
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
