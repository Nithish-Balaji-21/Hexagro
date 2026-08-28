<?php

namespace App\Support;

use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UnitScope
{
    public const SESSION_KEY = 'selected_units';

    /**
     * @return Collection<int, CostCenter>
     */
    public function visibleUnits(?User $user = null): Collection
    {
        $user ??= Auth::user();

        if ($user === null) {
            return collect();
        }

        if ($user->isAdmin()) {
            return CostCenter::query()->orderBy('name')->get();
        }

        $unitShareholders = config('hexagro.unit_shareholders', []);
        $userKey = $user->configKey();

        return CostCenter::query()
            ->whereIn('name', $this->unitNamesForUser($unitShareholders, $userKey))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<int>
     */
    public function visibleUnitIds(?User $user = null): array
    {
        return $this->visibleUnits($user)->pluck('id')->all();
    }

    /**
     * @return list<int>
     */
    public function selectedUnitIds(?User $user = null): array
    {
        $visibleIds = $this->visibleUnitIds($user);
        $selected = session(self::SESSION_KEY, $visibleIds);

        if (! is_array($selected)) {
            $selected = $visibleIds;
        }

        $selected = array_values(array_intersect(
            array_map('intval', $selected),
            $visibleIds,
        ));

        if ($selected === []) {
            $selected = $visibleIds;
        }

        return $selected;
    }

    /**
     * @return Collection<int, CostCenter>
     */
    public function selectedUnits(?User $user = null): Collection
    {
        $ids = $this->selectedUnitIds($user);

        return CostCenter::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<int>  $unitIds
     */
    public function setSelectedUnits(array $unitIds, ?User $user = null): void
    {
        $visibleIds = $this->visibleUnitIds($user);
        $sanitized = array_values(array_intersect(
            array_map('intval', $unitIds),
            $visibleIds,
        ));

        if ($sanitized === []) {
            $sanitized = $visibleIds;
        }

        session([self::SESSION_KEY => $sanitized]);
    }

    public function initializeForUser(User $user): void
    {
        session([self::SESSION_KEY => $this->visibleUnitIds($user)]);
    }

    public function isAllSelected(?User $user = null): bool
    {
        $visible = $this->visibleUnitIds($user);
        $selected = $this->selectedUnitIds($user);

        return count($visible) === count($selected);
    }

    public function scopeLabel(?User $user = null): string
    {
        $visible = $this->visibleUnits($user);
        $selected = $this->selectedUnits($user);

        if ($selected->count() === $visible->count()) {
            return $visible->count() > 1 ? 'All Units' : ($visible->first()?->name ?? '');
        }

        return $selected->pluck('name')->join(', ');
    }

    /**
     * @param  array<string, list<string>>  $unitShareholders
     * @return list<string>
     */
    private function unitNamesForUser(array $unitShareholders, string $userKey): array
    {
        $names = [];

        foreach ($unitShareholders as $unitName => $shareholders) {
            if (in_array($userKey, $shareholders, true)) {
                $names[] = $unitName;
            }
        }

        return $names;
    }
}
