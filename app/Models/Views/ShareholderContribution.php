<?php

namespace App\Models\Views;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\ReadOnlyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareholderContribution extends ReadOnlyModel
{
    /**
     * @var string
     */
    protected $table = 'v_shareholder_contribution';

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
            'contribution' => 'decimal:2',
        ];
    }
}
