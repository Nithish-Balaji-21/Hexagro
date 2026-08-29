<?php

namespace App\Console\Commands;

use App\Services\LedgerRebuildService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hexagro:rebuild-ledger {--entity= : Rebuild ledger for a single entity id}')]
#[Description('Rebuild materialized ledger entries from transaction data')]
class RebuildLedgerCommand extends Command
{
    public function handle(LedgerRebuildService $ledgerRebuildService): int
    {
        $entityId = $this->option('entity');

        if ($entityId !== null && $entityId !== '') {
            $ledgerRebuildService->rebuildForEntity((int) $entityId);
            $this->info("Ledger rebuilt for entity {$entityId}.");

            return self::SUCCESS;
        }

        $ledgerRebuildService->rebuildAll();
        $this->info('Ledger rebuilt for all entities.');

        return self::SUCCESS;
    }
}
