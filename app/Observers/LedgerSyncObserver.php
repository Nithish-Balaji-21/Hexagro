<?php

namespace App\Observers;

use App\Services\LedgerRebuildService;
use Illuminate\Database\Eloquent\Model;

class LedgerSyncObserver
{
    public function __construct(private LedgerRebuildService $ledgerRebuildService) {}

    public function created(Model $model): void
    {
        $this->ledgerRebuildService->rebuildForTransaction($model);
    }

    public function updated(Model $model): void
    {
        $this->ledgerRebuildService->rebuildForTransaction($model, $model->getOriginal());
    }

    public function deleted(Model $model): void
    {
        $this->ledgerRebuildService->rebuildForTransaction($model);
    }
}
