<?php

namespace App\Services\Dto;

use App\Models\CostCenter;

readonly class UnitSettlement
{
    /**
     * @param  list<PartnerSettlement>  $partners
     */
    public function __construct(
        public CostCenter $costCenter,
        public array $partners,
        public string $unitTotalCost,
        public string $alamNet,
        public string $ubiPool,
    ) {}
}
