<?php

namespace App\Services\Dto;

class ShareholderBar
{
    public function __construct(
        public string $name,
        public string $contribution,
        public string $fairShare,
    ) {}
}
