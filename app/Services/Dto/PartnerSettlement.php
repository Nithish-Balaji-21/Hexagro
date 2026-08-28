<?php

namespace App\Services\Dto;

use App\Models\Entity;

readonly class PartnerSettlement
{
    public function __construct(
        public Entity $entity,
        public string $sharePct,
        public string $paidDirectly,
        public string $alamShare,
        public string $ubiShare,
        public string $contribution,
        public string $fairShare,
        public string $net,
        public string $outstanding,
    ) {}
}
