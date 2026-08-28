<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $tableName,
        int|string $recordId,
        AuditAction $action,
        User $changedBy,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'changed_by' => $changedBy->id,
            'before_data' => $before,
            'after_data' => $after,
            'changed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function recordModel(
        Model $model,
        AuditAction $action,
        User $changedBy,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return $this->record(
            tableName: $model->getTable(),
            recordId: $model->getKey(),
            action: $action,
            changedBy: $changedBy,
            before: $before,
            after: $after,
        );
    }
}
