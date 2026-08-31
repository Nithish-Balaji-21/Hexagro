<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\Entity;
use App\Models\SettlementAdjustment;
use App\Models\SettlementLedgerEntry;
use App\Models\ShareholderShare;
use App\Services\Dto\EntityFundingRow;
use App\Services\Dto\OverallPartnerSettlement;
use App\Services\Dto\PartnerSettlement;
use App\Services\Dto\SuggestedTransfer;
use App\Services\Dto\UnitSettlement;
use App\Support\DateRange;
use App\Support\Money;
use Illuminate\Support\Collection;

class SettlementService
{
    public function __construct(private FundingBreakdownService $fundingBreakdown) {}

    public function forCostCenter(CostCenter $costCenter, ?DateRange $range = null): UnitSettlement
    {
        $rows = $this->fundingBreakdown->forCostCenter($costCenter, $range);
        $shares = $this->currentShares($costCenter);
        $alamNet = $this->alamNet($costCenter, $rows);
        $ubiPool = $this->ubiPool($rows);
        $ubiParticipants = $this->ubiParticipantKeys($costCenter);

        $partners = $shares->map(function (ShareholderShare $share) use ($rows, $alamNet, $ubiPool, $ubiParticipants): PartnerSettlement {
            $key = $share->entity->configKey();
            $paidDirectly = $this->fundingBreakdown->rowFor($rows, $share->entity)?->entityTotal ?? Money::zero();
            $alamShare = Money::mul($alamNet, $this->alamAttribution($key));
            $ubiShare = in_array($key, $ubiParticipants, true) && count($ubiParticipants) > 0
                ? Money::div($ubiPool, (string) count($ubiParticipants))
                : Money::zero();
            $contribution = Money::add(Money::add($paidDirectly, $alamShare), $ubiShare);

            return new PartnerSettlement(
                entity: $share->entity,
                sharePct: (string) $share->share_pct,
                paidDirectly: $paidDirectly,
                alamShare: $alamShare,
                ubiShare: $ubiShare,
                contribution: $contribution,
                fairShare: Money::zero(),
                net: Money::zero(),
                outstanding: Money::zero(),
            );
        });

        $unitTotalCost = $partners->reduce(
            fn (string $carry, PartnerSettlement $partner): string => Money::add($carry, $partner->contribution),
            Money::zero(),
        );

        $partners = $partners->map(function (PartnerSettlement $partner) use ($costCenter, $unitTotalCost): PartnerSettlement {
            $fairShare = Money::mul($unitTotalCost, $partner->sharePct);
            $net = Money::sub($partner->contribution, $fairShare);
            $outstanding = Money::add($net, $this->ledgerEffect($costCenter->name, $partner->entity));

            return new PartnerSettlement(
                entity: $partner->entity,
                sharePct: $partner->sharePct,
                paidDirectly: $partner->paidDirectly,
                alamShare: $partner->alamShare,
                ubiShare: $partner->ubiShare,
                contribution: $partner->contribution,
                fairShare: $fairShare,
                net: $net,
                outstanding: $outstanding,
            );
        })->values();

        return new UnitSettlement(
            costCenter: $costCenter,
            partners: $partners->all(),
            unitTotalCost: $unitTotalCost,
            alamNet: $alamNet,
            ubiPool: $ubiPool,
        );
    }

    /**
     * @param  list<int>|null  $costCenterIds
     * @return Collection<int, OverallPartnerSettlement>
     */
    public function overall(?array $costCenterIds = null): Collection
    {
        $costCenters = CostCenter::query()
            ->when($costCenterIds !== null, fn ($query) => $query->whereIn('id', $costCenterIds))
            ->orderBy('id')
            ->get();

        $unitSettlements = $costCenters->mapWithKeys(
            fn (CostCenter $costCenter): array => [$costCenter->id => $this->forCostCenter($costCenter)],
        );

        $partnersById = [];

        foreach ($unitSettlements as $costCenterId => $settlement) {
            foreach ($settlement->partners as $partner) {
                $id = $partner->entity->id;
                $partnersById[$id]['entity'] = $partner->entity;
                $partnersById[$id]['unit_nets'][$costCenterId] = $partner->net;
            }
        }

        $adjustments = $this->adjustmentEffects();
        $overallScope = (string) config('hexagro.overall_scope', 'Overall');

        return collect($partnersById)->map(function (array $row) use ($adjustments, $overallScope): OverallPartnerSettlement {
            $entity = $row['entity'];
            $unitNets = $row['unit_nets'];
            $overallNet = collect($unitNets)->reduce(
                fn (string $carry, string $net): string => Money::add($carry, $net),
                Money::zero(),
            );
            $adjustment = $adjustments[$entity->id] ?? Money::zero();
            $adjustedNet = Money::add($overallNet, $adjustment);
            $outstanding = Money::add($adjustedNet, $this->ledgerEffect($overallScope, $entity, includeUnitScopes: true));

            return new OverallPartnerSettlement(
                entity: $entity,
                unitNets: $unitNets,
                overallNet: $overallNet,
                adjustment: $adjustment,
                adjustedNet: $adjustedNet,
                outstanding: $outstanding,
            );
        })->values();
    }

    /**
     * Greedy minimum-transfer suggestions from outstanding nets.
     *
     * @param  list<array{entity: Entity, outstanding: string}>  $positions
     * @return list<SuggestedTransfer>
     */
    public function suggestedTransfers(array $positions): array
    {
        $threshold = (string) config('hexagro.suggested_transfer_threshold', 0.5);

        $receivers = collect($positions)
            ->filter(fn (array $position): bool => Money::cmp($position['outstanding'], $threshold) > 0)
            ->map(fn (array $position): array => [
                'entity' => $position['entity'],
                'amount' => $position['outstanding'],
            ])
            ->sortByDesc(fn (array $row): string => $row['amount'])
            ->values()
            ->all();

        $payers = collect($positions)
            ->filter(fn (array $position): bool => Money::cmp($position['outstanding'], Money::mul($threshold, '-1')) < 0)
            ->map(fn (array $position): array => [
                'entity' => $position['entity'],
                'amount' => Money::abs($position['outstanding']),
            ])
            ->sortByDesc(fn (array $row): string => $row['amount'])
            ->values()
            ->all();

        $transfers = [];
        $i = 0;
        $j = 0;

        while ($i < count($payers) && $j < count($receivers)) {
            $amount = Money::cmp($payers[$i]['amount'], $receivers[$j]['amount']) < 0
                ? $payers[$i]['amount']
                : $receivers[$j]['amount'];

            if (Money::cmp($amount, $threshold) > 0) {
                $transfers[] = new SuggestedTransfer(
                    from: $payers[$i]['entity'],
                    to: $receivers[$j]['entity'],
                    amount: $amount,
                );
            }

            $payers[$i]['amount'] = Money::sub($payers[$i]['amount'], $amount);
            $receivers[$j]['amount'] = Money::sub($receivers[$j]['amount'], $amount);

            if (Money::cmp($payers[$i]['amount'], $threshold) <= 0) {
                $i++;
            }

            if (Money::cmp($receivers[$j]['amount'], $threshold) <= 0) {
                $j++;
            }
        }

        return $transfers;
    }

    /**
     * @return Collection<int, ShareholderShare>
     */
    private function currentShares(CostCenter $costCenter): Collection
    {
        return ShareholderShare::query()
            ->with('entity')
            ->where('cost_center_id', $costCenter->id)
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->orderBy('id')
            ->get()
            ->unique('entity_id')
            ->sortBy('entity_id')
            ->values();
    }

    /**
     * @param  Collection<int, EntityFundingRow>  $rows
     */
    private function alamNet(CostCenter $costCenter, Collection $rows): string
    {
        $alamRow = $rows->first(fn (EntityFundingRow $row): bool => $row->entity->isAlam());

        return $alamRow?->entityTotal ?? Money::zero();
    }

    /**
     * @param  Collection<int, EntityFundingRow>  $rows
     */
    private function ubiPool(Collection $rows): string
    {
        return $rows
            ->filter(fn (EntityFundingRow $row): bool => $row->entity->isBankAccount())
            ->reduce(
                fn (string $carry, EntityFundingRow $row): string => Money::add($carry, $row->entityTotal),
                Money::zero(),
            );
    }

    /**
     * @return list<string>
     */
    private function ubiParticipantKeys(CostCenter $costCenter): array
    {
        /** @var array<string, list<string>> $participants */
        $participants = config('hexagro.ubi_participants', []);

        return $participants[$costCenter->name] ?? [];
    }

    private function alamAttribution(?string $key): string
    {
        if ($key === null) {
            return Money::zero();
        }

        /** @var array<string, float|int|string> $attribution */
        $attribution = config('hexagro.alam_attribution', []);

        return Money::of($attribution[$key] ?? 0);
    }

    private function ledgerEffect(string $unitScope, Entity $entity, bool $includeUnitScopes = false): string
    {
        $query = SettlementLedgerEntry::query();

        if ($includeUnitScopes) {
            $query->where(function ($inner) use ($unitScope): void {
                $inner->where('unit_scope', $unitScope)
                    ->orWhereIn('unit_scope', CostCenter::query()->pluck('name'));
            });
        } else {
            $query->where('unit_scope', $unitScope);
        }

        $paid = (clone $query)->where('from_entity_id', $entity->id)->sum('amount');
        $received = (clone $query)->where('to_entity_id', $entity->id)->sum('amount');

        return Money::sub((string) $paid, (string) $received);
    }

    /**
     * @return array<int, string>
     */
    private function adjustmentEffects(): array
    {
        $effects = [];

        SettlementAdjustment::query()->each(function (SettlementAdjustment $adjustment) use (&$effects): void {
            $fromId = $adjustment->from_entity_id;
            $toId = $adjustment->to_entity_id;
            $effects[$fromId] = Money::sub($effects[$fromId] ?? Money::zero(), $adjustment->amount);
            $effects[$toId] = Money::add($effects[$toId] ?? Money::zero(), $adjustment->amount);
        });

        return $effects;
    }
}
