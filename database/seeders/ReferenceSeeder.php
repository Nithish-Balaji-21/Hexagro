<?php

namespace Database\Seeders;

use App\Enums\EntityType;
use App\Enums\UserRole;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\ShareholderShare;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    /**
     * Seed users, cost centers, entities, and shareholder shares.
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedCostCenters();
        $this->seedEntities();
        $this->seedShareholderShares();
    }

    private function seedUsers(): void
    {
        foreach ([
            ['name' => 'Jagadeesan', 'initials' => 'JD', 'role' => UserRole::Admin],
            ['name' => 'Jagadeshwaran', 'initials' => 'JW', 'role' => UserRole::Viewer],
            ['name' => 'Vellingiri', 'initials' => 'VG', 'role' => UserRole::Viewer],
            ['name' => 'Vikas', 'initials' => 'VK', 'role' => UserRole::Viewer],
        ] as $user) {
            User::query()->firstOrCreate(
                ['name' => $user['name']],
                [
                    'initials' => $user['initials'],
                    'role' => $user['role'],
                    'password_hash' => null,
                ],
            );
        }
    }

    private function seedCostCenters(): void
    {
        foreach (['Fibre Unit', 'Chips Unit', 'Washing Unit'] as $name) {
            CostCenter::query()->firstOrCreate(['name' => $name]);
        }
    }

    private function seedEntities(): void
    {
        foreach ([
            ['name' => 'Shareholder - Jagadeesan', 'short_name' => 'Jagadeesan', 'entity_type' => EntityType::Shareholder],
            ['name' => 'Shareholder - Jagadeshwaran', 'short_name' => 'Jagadeshwaran (JW)', 'entity_type' => EntityType::Shareholder],
            ['name' => 'Shareholder - Vellingiri', 'short_name' => 'Vellingiri', 'entity_type' => EntityType::Shareholder],
            ['name' => 'Vikas', 'short_name' => 'Vikas', 'entity_type' => EntityType::Shareholder],
            ['name' => 'Payable to Alam', 'short_name' => 'Alam', 'entity_type' => EntityType::NonShareholderFunder],
            ['name' => 'Union Bank - CC', 'short_name' => 'Bank — CC', 'entity_type' => EntityType::BankAccount],
            ['name' => 'Union Bank - Current', 'short_name' => 'Bank — Current', 'entity_type' => EntityType::BankAccount],
            ['name' => 'Union Bank - Term Loan', 'short_name' => 'Bank — Term Loan', 'entity_type' => EntityType::BankAccount],
        ] as $entity) {
            Entity::query()->firstOrCreate(
                ['name' => $entity['name']],
                [
                    'short_name' => $entity['short_name'],
                    'entity_type' => $entity['entity_type'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedShareholderShares(): void
    {
        $effectiveFrom = '2026-04-01';

        $shares = [
            'Fibre Unit' => [
                'Shareholder - Jagadeesan' => '0.2222',
                'Shareholder - Jagadeshwaran' => '0.2222',
                'Shareholder - Vellingiri' => '0.2222',
                'Vikas' => '0.3333',
            ],
            'Chips Unit' => [
                'Shareholder - Jagadeesan' => '0.3333',
                'Shareholder - Jagadeshwaran' => '0.3333',
                'Shareholder - Vellingiri' => '0.3333',
            ],
            'Washing Unit' => [
                'Shareholder - Jagadeesan' => '0.3333',
                'Shareholder - Jagadeshwaran' => '0.3333',
                'Shareholder - Vellingiri' => '0.3333',
            ],
        ];

        foreach ($shares as $unitName => $partners) {
            $costCenter = CostCenter::query()->where('name', $unitName)->firstOrFail();

            foreach ($partners as $entityName => $sharePct) {
                $entity = Entity::query()->where('name', $entityName)->firstOrFail();

                ShareholderShare::query()->firstOrCreate(
                    [
                        'cost_center_id' => $costCenter->id,
                        'entity_id' => $entity->id,
                        'effective_from' => $effectiveFrom,
                    ],
                    ['share_pct' => $sharePct],
                );
            }
        }
    }
}
