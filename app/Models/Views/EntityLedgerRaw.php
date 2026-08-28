<?php

namespace App\Models\Views;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\ReadOnlyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityLedgerRaw extends ReadOnlyModel
{
    /**
     * @var string
     */
    protected $table = 'v_entity_ledger_raw';

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
        ];
    }
}
