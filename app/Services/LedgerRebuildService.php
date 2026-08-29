<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\Entity;
use App\Models\LedgerEntry;
use App\Models\Transfer;
use App\Models\Views\EntityLedgerRaw;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LedgerRebuildService
{
    public function __construct(private AuditLogService $auditLogService) {}

    public function rebuildAll(): void
    {
        Entity::query()->active()->pluck('id')->each(function (int $entityId): void {
            $this->rebuildForEntity($entityId);
        });
    }

    public function rebuildForEntity(int $entityId): void
    {
        DB::transaction(function () use ($entityId): void {
            LedgerEntry::query()->where('entity_id', $entityId)->delete();

            $rawRows = EntityLedgerRaw::query()
                ->where('entity_id', $entityId)
                ->orderBy('txn_date')
                ->orderBy('source_table')
                ->orderBy('source_id')
                ->get();

            foreach ($rawRows as $raw) {
                $signed = Money::of($raw->signed_amount);
                $isCredit = Money::cmp($signed, '0') > 0;

                LedgerEntry::query()->create([
                    'entity_id' => $entityId,
                    'txn_date' => $raw->txn_date,
                    'cost_center_id' => $raw->cost_center_id,
                    'particulars' => (string) $raw->particulars,
                    'signed_amount' => $signed,
                    'debit' => $isCredit ? Money::zero() : Money::abs($signed),
                    'credit' => $isCredit ? $signed : Money::zero(),
                    'source_table' => (string) $raw->source_table,
                    'source_id' => (int) $raw->source_id,
                ]);
            }

            $this->recordRebuildAudit($entityId, $rawRows->count());
        });
    }

    /**
     * @param  array<string, mixed>|null  $original
     */
    public function rebuildForTransaction(Model $model, ?array $original = null): void
    {
        $entityIds = $this->entityIdsForModel($model);

        if ($original !== null) {
            $entityIds = array_values(array_unique(array_merge(
                $entityIds,
                $this->entityIdsFromAttributes($original, $model::class),
            )));
        }

        foreach ($entityIds as $entityId) {
            $this->rebuildForEntity($entityId);
        }
    }

    /**
     * @return list<int>
     */
    private function entityIdsForModel(Model $model): array
    {
        return $this->entityIdsFromAttributes($model->getAttributes(), $model::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<int>
     */
    private function entityIdsFromAttributes(array $attributes, string $modelClass): array
    {
        return match ($modelClass) {
            DebitTransaction::class => isset($attributes['paid_through_entity_id'])
                ? [(int) $attributes['paid_through_entity_id']]
                : [],
            CreditTransaction::class => isset($attributes['received_to_entity_id'])
                ? [(int) $attributes['received_to_entity_id']]
                : [],
            Transfer::class => array_values(array_filter([
                isset($attributes['from_entity_id']) ? (int) $attributes['from_entity_id'] : null,
                isset($attributes['to_entity_id']) ? (int) $attributes['to_entity_id'] : null,
            ])),
            default => [],
        };
    }

    private function recordRebuildAudit(int $entityId, int $rowCount): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $this->auditLogService->record(
            tableName: 'ledger_entries',
            recordId: $entityId,
            action: AuditAction::Rebuild,
            changedBy: $user,
            before: null,
            after: ['entity_id' => $entityId, 'row_count' => $rowCount],
        );
    }
}
