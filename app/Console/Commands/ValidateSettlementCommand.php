<?php

namespace App\Console\Commands;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Services\SettlementService;
use App\Support\Money;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hexagro:validate-settlement')]
#[Description('Compare computed settlement figures to prototype targets')]
class ValidateSettlementCommand extends Command
{
    private const TOLERANCE = '0.01';

    /**
     * @var array<string, string>
     */
    private const FIBRE_JAGADEESAN_TARGETS = [
        'contribution' => '954706.30',
        'fair_share' => '639925.42',
        'net' => '314780.88',
    ];

    /**
     * Execute the console command.
     */
    public function handle(SettlementService $settlement): int
    {
        $fibre = CostCenter::query()->where('name', config('hexagro.fibre_unit_name'))->first();

        if ($fibre === null) {
            $this->error('Fibre Unit cost center not found.');

            return self::FAILURE;
        }

        $result = $settlement->forCostCenter($fibre);
        $jagadeesan = Entity::query()->where('name', 'Shareholder - Jagadeesan')->first();

        if ($jagadeesan === null) {
            $this->error('Jagadeesan entity not found.');

            return self::FAILURE;
        }

        $partner = collect($result->partners)->first(fn ($row) => $row->entity->is($jagadeesan));

        if ($partner === null) {
            $this->error('Jagadeesan not found in Fibre settlement.');

            return self::FAILURE;
        }

        $this->info('Fibre Unit — Jagadeesan settlement comparison');
        $this->newLine();

        $rows = [];
        $hasFailure = false;

        foreach (self::FIBRE_JAGADEESAN_TARGETS as $field => $target) {
            $actual = match ($field) {
                'contribution' => $partner->contribution,
                'fair_share' => $partner->fairShare,
                'net' => $partner->net,
            };

            $delta = Money::sub($actual, $target);
            $withinTolerance = Money::cmp(Money::abs($delta), self::TOLERANCE) <= 0;

            if (! $withinTolerance) {
                $hasFailure = true;
            }

            $rows[] = [
                $field,
                $target,
                Money::round($actual),
                Money::round($delta),
                $withinTolerance ? 'PASS' : 'FAIL',
            ];
        }

        $this->table(['Field', 'Target', 'Actual', 'Delta', 'Status'], $rows);

        if ($hasFailure) {
            $this->newLine();
            $this->error('Settlement validation failed. Import data may be incomplete or totals may have drifted.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Settlement validation passed within ₹'.self::TOLERANCE.' tolerance.');

        return self::SUCCESS;
    }
}
