<?php

namespace App\Models\Views;

use App\Models\CostCenter;
use App\Models\ReadOnlyModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayablesByUnit extends ReadOnlyModel
{
    /**
     * @var string
     */
    protected $table = 'v_payables_by_unit';

    /**
     * @var string
     */
    protected $primaryKey = 'cost_center_id';

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
            'total_payable' => 'decimal:2',
        ];
    }
}
