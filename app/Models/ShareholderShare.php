<?php

namespace App\Models;

use Database\Factories\ShareholderShareFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cost_center_id', 'entity_id', 'share_pct', 'effective_from'])]
class ShareholderShare extends Model
{
    /** @use HasFactory<ShareholderShareFactory> */
    use HasFactory;

    public $timestamps = false;

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
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'share_pct' => 'decimal:4',
            'effective_from' => 'date',
        ];
    }
}
