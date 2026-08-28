<?php

namespace App\Console\Commands;

use App\Models\CostCenter;
use App\Services\SettlementService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hexagro:validate-settlement')]
#[Description('Compare computed settlement figures to prototype targets (stub until Zoho export)')]
class ValidateSettlementCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SettlementService $settlement): int
    {
        $this->info('ready for Zoho export');

        $fibre = CostCenter::query()->where('name', config('hexagro.fibre_unit_name'))->first();

        if ($fibre !== null) {
            $result = $settlement->forCostCenter($fibre);
            $this->comment('Fibre Unit partners: '.count($result->partners).' (transactions not yet imported)');
        }

        return self::SUCCESS;
    }
}
