<?php

namespace Database\Factories;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\ShareholderShare;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShareholderShare>
 */
class ShareholderShareFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cost_center_id' => CostCenter::factory(),
            'entity_id' => Entity::factory(),
            'share_pct' => '0.3333',
            'effective_from' => '2026-04-01',
        ];
    }
}
