<?php

namespace App\Services\Dto;

use App\Models\Entity;

readonly class SuggestedTransfer
{
    public function __construct(
        public Entity $from,
        public Entity $to,
        public string $amount,
    ) {}
}
