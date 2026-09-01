<?php

namespace App\Livewire\Concerns;

use App\Models\OutstandingBatch;
use App\Models\OutstandingLine;
use App\Support\Inr;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;

trait WithOutstandingBatches
{
    use AuthorizesRequests;

    public ?int $selectedBatchId = null;

    public bool $showBatchForm = false;

    public ?int $editingBatchId = null;

    public string $formBatchDate = '';

    /** @var list<array{party: string, cost_center_id: string, amount: string, notes: string}> */
    public array $lineRows = [];

    protected function outstandingKind(): string
    {
        return 'payable';
    }

    protected function outstandingPageTitle(): string
    {
        return 'Payables';
    }

    protected function outstandingBatchHeading(): string
    {
        return 'Outstanding Payments';
    }

    protected function outstandingBatchNote(): string
    {
        return 'Informational — unpaid amounts are not part of the spend or settlement figures.';
    }

    public function viewBatch(int $id): void
    {
        $batch = $this->batchQuery()->findOrFail($id);
        $this->authorize('view', $batch);
        $this->selectedBatchId = $id;
        $this->showBatchForm = false;
        $this->editingBatchId = null;
    }

    public function backToList(): void
    {
        $this->selectedBatchId = null;
        $this->showBatchForm = false;
        $this->editingBatchId = null;
        $this->resetBatchForm();
    }

    public function openCreateBatch(): void
    {
        $this->authorize('create', OutstandingBatch::class);
        $this->selectedBatchId = null;
        $this->resetBatchForm();
        $this->formBatchDate = now()->toDateString();
        $this->lineRows = [$this->emptyLineRow()];
        $this->showBatchForm = true;
    }

    public function openEditBatch(?int $id = null): void
    {
        $batchId = $id ?? $this->selectedBatchId;
        if ($batchId === null) {
            return;
        }

        $batch = $this->batchQuery()->with('lines')->findOrFail($batchId);
        $this->authorize('update', $batch);

        $this->editingBatchId = $batch->id;
        $this->selectedBatchId = $batch->id;
        $this->formBatchDate = $batch->batch_date->toDateString();
        $this->lineRows = $batch->lines->map(fn (OutstandingLine $line): array => [
            'party' => $line->party_name,
            'cost_center_id' => (string) $line->cost_center_id,
            'amount' => (string) $line->amount,
            'notes' => $line->notes ?? '',
        ])->all();

        if ($this->lineRows === []) {
            $this->lineRows = [$this->emptyLineRow()];
        }

        $this->showBatchForm = true;
    }

    public function copyBatch(?int $id = null): void
    {
        $batchId = $id ?? $this->selectedBatchId;
        if ($batchId === null) {
            return;
        }

        $this->authorize('create', OutstandingBatch::class);

        $batch = $this->batchQuery()->with('lines')->findOrFail($batchId);

        $this->editingBatchId = null;
        $this->selectedBatchId = null;
        $this->formBatchDate = now()->toDateString();
        $this->lineRows = $batch->lines->map(fn (OutstandingLine $line): array => [
            'party' => $line->party_name,
            'cost_center_id' => (string) $line->cost_center_id,
            'amount' => (string) $line->amount,
            'notes' => $line->notes ?? '',
        ])->all();

        if ($this->lineRows === []) {
            $this->lineRows = [$this->emptyLineRow()];
        }

        $this->showBatchForm = true;
        $this->dispatch('toast', message: 'Batch copied. Edit values and save as a new batch.');
    }

    public function loadLinesFromBatch(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $batch = $this->batchQuery()->with('lines')->find($id);
        if (! $batch) {
            return;
        }

        $this->lineRows = $batch->lines->map(fn (OutstandingLine $line): array => [
            'party' => $line->party_name,
            'cost_center_id' => (string) $line->cost_center_id,
            'amount' => (string) $line->amount,
            'notes' => $line->notes ?? '',
        ])->all();

        if ($this->lineRows === []) {
            $this->lineRows = [$this->emptyLineRow()];
        }

        $this->dispatch('toast', message: 'Loaded items from batch of '.\App\Support\Inr::formatDatePicker($batch->batch_date->toDateString()));
    }

    public function addLineRow(): void
    {
        $this->lineRows[] = $this->emptyLineRow();
    }

    public function removeLineRow(int $index): void
    {
        if (count($this->lineRows) <= 1) {
            $this->lineRows = [$this->emptyLineRow()];

            return;
        }

        unset($this->lineRows[$index]);
        $this->lineRows = array_values($this->lineRows);
    }

    public function saveBatch(): void
    {
        $this->authorize('create', OutstandingBatch::class);

        $parsedDate = Inr::parseDatePicker($this->formBatchDate);
        if ($parsedDate === null) {
            $this->addError('formBatchDate', 'Enter a valid date as dd/mm/yyyy.');

            return;
        }

        $validated = $this->validate([
            'formBatchDate' => ['required', 'string'],
            'lineRows' => ['required', 'array', 'min:1'],
            'lineRows.*.party' => ['required', 'string', 'max:150'],
            'lineRows.*.cost_center_id' => ['required', Rule::exists('cost_centers', 'id')],
            'lineRows.*.amount' => ['required', 'numeric'],
            'lineRows.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->editingBatchId) {
            $batch = $this->batchQuery()->findOrFail($this->editingBatchId);
            $this->authorize('update', $batch);
            $batch->update([
                'batch_date' => $parsedDate,
            ]);
            $batch->lines()->delete();
        } else {
            $batch = OutstandingBatch::query()->create([
                'kind' => $this->outstandingKind(),
                'batch_date' => $parsedDate,
                'created_by' => auth()->id(),
            ]);
        }

        foreach ($validated['lineRows'] as $row) {
            OutstandingLine::query()->create([
                'batch_id' => $batch->id,
                'cost_center_id' => (int) $row['cost_center_id'],
                'party_name' => $row['party'],
                'amount' => $row['amount'],
                'notes' => $row['notes'] ?: null,
            ]);
        }

        $this->dispatch('toast', message: $this->outstandingPageTitle().' batch saved.');
        $this->showBatchForm = false;
        $this->editingBatchId = null;
        $this->selectedBatchId = $batch->id;
        $this->resetBatchForm();
    }

    public function deleteBatch(int $id): void
    {
        $batch = $this->batchQuery()->findOrFail($id);
        $this->authorize('delete', $batch);
        $batch->delete();

        if ($this->selectedBatchId === $id) {
            $this->selectedBatchId = null;
        }

        $this->dispatch('toast', message: 'Batch deleted.');
    }

    public function closeBatchForm(): void
    {
        $this->showBatchForm = false;
        $this->editingBatchId = null;
        $this->resetBatchForm();
    }

    protected function batchQuery(): Builder
    {
        return OutstandingBatch::query()->where('kind', $this->outstandingKind());
    }

    protected function batches(): LengthAwarePaginator
    {
        $unitIds = $this->scopedUnitIds();

        return $this->batchQuery()
            ->withCount(['lines' => fn (Builder $query) => $query->whereIn('cost_center_id', $unitIds)])
            ->withSum(['lines as scoped_total' => fn (Builder $query) => $query->whereIn('cost_center_id', $unitIds)], 'amount')
            ->with('createdBy')
            ->orderByDesc('batch_date')
            ->orderByDesc('id')
            ->paginate(10);
    }

    protected function recentBatches()
    {
        return $this->batchQuery()
            ->withCount('lines')
            ->orderByDesc('batch_date')
            ->orderByDesc('id')
            ->take(30)
            ->get();
    }

    protected function selectedBatch(): ?OutstandingBatch
    {
        if ($this->selectedBatchId === null) {
            return null;
        }

        $unitIds = $this->scopedUnitIds();
        $batch = $this->batchQuery()
            ->with(['lines' => fn ($query) => $query->whereIn('cost_center_id', $unitIds)->with('costCenter'), 'createdBy'])
            ->find($this->selectedBatchId);

        return $batch;
    }

    protected function computedLineTotal(): string
    {
        $total = Money::zero();

        foreach ($this->lineRows as $row) {
            if ($row['amount'] === '' || ! is_numeric($row['amount'])) {
                continue;
            }

            $total = Money::add($total, $row['amount']);
        }

        return $total;
    }

    private function emptyLineRow(): array
    {
        $defaultUnit = $this->scopedUnits()->first();

        return [
            'party' => '',
            'cost_center_id' => (string) ($defaultUnit?->id ?? ''),
            'amount' => '',
            'notes' => '',
        ];
    }

    private function resetBatchForm(): void
    {
        $this->formBatchDate = '';
        $this->lineRows = [];
        $this->resetValidation();
    }
}
