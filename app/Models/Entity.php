<?php

namespace App\Models;

use App\Enums\EntityType;
use Database\Factories\EntityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'short_name', 'entity_type', 'is_active'])]
class Entity extends Model
{
    /** @use HasFactory<EntityFactory> */
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
    public function paidThroughDebits(): HasMany
    {
        return $this->hasMany(DebitTransaction::class, 'paid_through_entity_id');
    }

    /**
     * @return HasMany<CreditTransaction, $this>
     */
    public function receivedCredits(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'received_to_entity_id');
    }

    /**
     * @return HasMany<Transfer, $this>
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_entity_id');
    }

    /**
     * @return HasMany<Transfer, $this>
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_entity_id');
    }

    public function configKey(): ?string
    {
        /** @var array<string, string> $keys */
        $keys = config('hexagro.entity_keys', []);

        return $keys[$this->name] ?? null;
    }

    public function isShareholder(): bool
    {
        return $this->entity_type === EntityType::Shareholder;
    }

    public function isBankAccount(): bool
    {
        return $this->entity_type === EntityType::BankAccount;
    }

    public function isAlam(): bool
    {
        return $this->entity_type === EntityType::NonShareholderFunder;
    }

    /**
     * @param  Builder<Entity>  $query
     * @return Builder<Entity>
     */
    #[Scope]
    protected function shareholders(Builder $query): Builder
    {
        return $query->where('entity_type', EntityType::Shareholder);
    }

    /**
     * @param  Builder<Entity>  $query
     * @return Builder<Entity>
     */
    #[Scope]
    protected function bankAccounts(Builder $query): Builder
    {
        return $query->where('entity_type', EntityType::BankAccount);
    }

    /**
     * @param  Builder<Entity>  $query
     * @return Builder<Entity>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => EntityType::class,
            'is_active' => 'boolean',
        ];
    }
}
