<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\SettlementAdjustment;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettlementAdjustmentSeeder extends Seeder
{
    /**
     * Seed the Jagadeshwaran → Vellingiri manual true-up.
     */
    public function run(): void
    {
        $admin = User::query()->where('name', 'Jagadeesan')->firstOrFail();
        $from = Entity::query()->where('name', 'Shareholder - Jagadeshwaran')->firstOrFail();
        $to = Entity::query()->where('name', 'Shareholder - Vellingiri')->firstOrFail();

        SettlementAdjustment::query()->firstOrCreate(
            [
                'from_entity_id' => $from->id,
                'to_entity_id' => $to->id,
                'amount' => '116980.00',
            ],
            [
                'note' => 'Manual true-up between Jagadeshwaran and Vellingiri',
                'created_by' => $admin->id,
            ],
        );
    }
}
