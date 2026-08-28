<?php

namespace App\Services\Dto;

use App\Models\Entity;

readonly class EntityFundingRow
{
    public function __construct(
        public Entity $entity,
        public string $expenses,
        public string $rawMaterials,
        public string $otherDebits,
        public string $credits,
        public string $entityTotal,
    ) {}
}
