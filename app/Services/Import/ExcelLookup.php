<?php

namespace App\Services\Import;

use App\Enums\CreditType;
use App\Enums\DebitCategory;
use App\Enums\UserRole;
use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\OutstandingLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelLookup
{
    /** @var Collection<string, int> */
    private Collection $costCenters;

    /** @var Collection<string, int> */
    private Collection $entities;

    private int $adminUserId;

    public function __construct()
    {
        $this->costCenters = CostCenter::query()
            ->pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name): array => [mb_strtolower(trim($name)) => $id]);

        $this->entities = Entity::query()
            ->get()
            ->mapWithKeys(fn (Entity $entity): array => [mb_strtolower(trim($entity->name)) => $entity->id]);

        foreach (config('hexagro.entity_keys', []) as $entityName => $key) {
            $id = $this->entities->get(mb_strtolower(trim($entityName)));
            if ($id !== null) {
                $this->entities->put(mb_strtolower($key), $id);
            }
        }

        $this->registerEntityAliases();

        $this->adminUserId = auth()->id()
            ?? User::query()->where('role', UserRole::Admin)->orderBy('id')->value('id')
            ?? throw new InvalidArgumentException('An admin user must exist before importing.');
    }

    public function adminUserId(): int
    {
        return $this->adminUserId;
    }

    public function costCenterId(string $name): int
    {
        $id = $this->costCenters->get(mb_strtolower(trim($name)));

        if ($id === null) {
            throw new InvalidArgumentException("Unknown cost center: {$name}");
        }

        return $id;
    }

    public function entityId(string $name): int
    {
        $id = $this->entities->get(mb_strtolower(trim($name)));

        if ($id === null) {
            throw new InvalidArgumentException("Unknown entity: {$name}");
        }

        return $id;
    }

    public function parseDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Date is required.');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }

    public function parseAmount(mixed $value): string
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Amount is required.');
        }

        $normalized = str_replace([',', '₹', ' '], '', (string) $value);
        $amount = (float) $normalized;

        if ($amount <= 0) {
            throw new InvalidArgumentException("Amount must be positive, got: {$value}");
        }

        return number_format($amount, 2, '.', '');
    }

    public function parseOptionalAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace([',', '₹', ' '], '', (string) $value);
        $amount = (float) $normalized;

        if ($amount === 0.0) {
            return null;
        }

        return number_format(abs($amount), 2, '.', '');
    }

    public function debitCategory(string $type): DebitCategory
    {
        $normalized = mb_strtolower(trim($type));

        return match ($normalized) {
            'expense' => DebitCategory::Expense,
            'raw materials' => DebitCategory::RawMaterials,
            default => throw new InvalidArgumentException("Unknown debit category: {$type}"),
        };
    }

    public function creditType(string $type): CreditType
    {
        $normalized = mb_strtolower(trim($type));

        return match ($normalized) {
            'sales' => CreditType::Sales,
            'vendor return' => CreditType::VendorReturn,
            'employee return' => CreditType::EmployeeReturn,
            'other credit' => CreditType::OtherCredit,
            default => throw new InvalidArgumentException("Unknown credit type: {$type}"),
        };
    }

    public function isTransferFund(string $type): bool
    {
        return str_contains(mb_strtolower(trim($type)), 'transfer');
    }

    /**
     * @return 'payable'|'receivable'|null
     */
    public function outstandingKind(string $party, ?string $type = null): ?string
    {
        if ($type !== null && trim($type) !== '') {
            return $this->parseOutstandingType($type);
        }

        /** @var array<string, string> $kinds */
        $kinds = config('hexagro.outstanding_party_kinds', []);
        $trimmed = trim($party);

        if (isset($kinds[$trimmed])) {
            return $kinds[$trimmed];
        }

        $existingKind = OutstandingLine::query()
            ->where('party_name', $trimmed)
            ->join('outstanding_batches', 'outstanding_batches.id', '=', 'outstanding_lines.batch_id')
            ->value('outstanding_batches.kind');

        if (is_string($existingKind) && in_array($existingKind, ['payable', 'receivable'], true)) {
            return $existingKind;
        }

        return null;
    }

    /**
     * @return 'payable'|'receivable'
     */
    public function parseOutstandingType(string $type): string
    {
        $normalized = mb_strtolower(trim($type));

        return match ($normalized) {
            'receivable', 'receivables', 'sales', 'sale' => 'receivable',
            'payable', 'payables', 'purchase', 'purchases' => 'payable',
            default => throw new InvalidArgumentException("Unknown outstanding type: {$type}"),
        };
    }

    private function registerEntityAliases(): void
    {
        $aliases = [
            'jagadeesan' => 'Shareholder - Jagadeesan',
            'jagadeshwaran' => 'Shareholder - Jagadeshwaran',
            'jagadeshwaran (jw)' => 'Shareholder - Jagadeshwaran',
            'vellingiri' => 'Shareholder - Vellingiri',
            'vikas' => 'Vikas',
            'alam' => 'Payable to Alam',
            'payable to alam' => 'Payable to Alam',
            'bank — cc' => 'Union Bank - CC',
            'bank — current' => 'Union Bank - Current',
            'bank — term loan' => 'Union Bank - Term Loan',
        ];

        foreach ($aliases as $alias => $entityName) {
            $id = $this->entities->get(mb_strtolower($entityName));
            if ($id !== null) {
                $this->entities->put($alias, $id);
            }
        }
    }
}
