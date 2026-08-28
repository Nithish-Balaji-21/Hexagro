<?php

namespace App\Services\Dto;

use App\Models\Entity;

readonly class OverallPartnerSettlement
{
    /**
     * @param  array<int, string>  $unitNets  cost_center_id => net
     */
    public function __construct(
        public Entity $entity,
        public array $unitNets,
        public string $overallNet,
        public string $adjustment,
        public string $adjustedNet,
        public string $outstanding,
    ) {}
}
