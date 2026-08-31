<?php

namespace App\Services\Import;

use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\ImportRun;
use App\Models\Transfer;
use App\Services\LedgerRebuildService;
use Illuminate\Support\Facades\DB;

class ImportRunService
{
    public function __construct(private LedgerRebuildService $ledgerRebuildService) {}

    public function start(string $kind, string $filename, int $userId): ImportRun
    {
        if (! in_array($kind, ImportRun::KINDS, true)) {
            throw new \InvalidArgumentException("Unsupported import kind: {$kind}");
        }

        return ImportRun::query()->create([
            'kind' => $kind,
            'filename' => $filename,
            'user_id' => $userId,
            'row_count' => 0,
        ]);
    }

    public function finish(ImportRun $run, int $createdCount): ImportRun
    {
        $run->update(['row_count' => $createdCount]);

        return $run->refresh();
    }

    public function latestForKind(string $kind): ?ImportRun
    {
        return ImportRun::query()
            ->where('kind', $kind)
            ->where('row_count', '>', 0)
            ->orderByDesc('id')
            ->first();
    }

    public function revert(ImportRun $run): int
    {
        return DB::transaction(function () use ($run): int {
            $deleted = match ($run->kind) {
                'debit' => DebitTransaction::query()->where('import_run_id', $run->id)->delete(),
                'credit' => CreditTransaction::query()->where('import_run_id', $run->id)->delete(),
                'transfers' => Transfer::query()->where('import_run_id', $run->id)->delete(),
                default => 0,
            };

            $run->delete();

            if ($deleted > 0) {
                $this->ledgerRebuildService->rebuildAll();
            }

            return $deleted;
        });
    }
}
