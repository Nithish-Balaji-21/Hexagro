<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditableObserver
{
    public function __construct(private AuditLogService $auditLogService) {}

    public function created(Model $model): void
    {
        $this->record($model, AuditAction::Create, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->record(
            $model,
            AuditAction::Update,
            $model->getOriginal(),
            $model->getAttributes(),
        );
    }

    public function deleted(Model $model): void
    {
        $this->record($model, AuditAction::Delete, $model->getAttributes(), null);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function record(Model $model, AuditAction $action, ?array $before, ?array $after): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $this->auditLogService->recordModel(
            model: $model,
            action: $action,
            changedBy: $user,
            before: $before,
            after: $after,
        );
    }
}
