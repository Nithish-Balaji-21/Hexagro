<?php

namespace App\Providers;

use App\Models\BankingSnapshot;
use App\Models\CreditTransaction;
use App\Models\DebitTransaction;
use App\Models\HistoricalAlamExpense;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SettlementAdjustment;
use App\Models\Transfer;
use App\Observers\AuditableObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::before(function ($user, string $ability) {
            if ($user === null) {
                return null;
            }

            if (in_array($ability, ['create', 'update', 'delete', 'restore', 'forceDelete'], true)) {
                return $user->isAdmin() ? null : false;
            }

            return null;
        });

        $auditableModels = [
            DebitTransaction::class,
            CreditTransaction::class,
            Transfer::class,
            Purchase::class,
            Sale::class,
            BankingSnapshot::class,
            HistoricalAlamExpense::class,
            SettlementAdjustment::class,
        ];

        foreach ($auditableModels as $model) {
            $model::observe(AuditableObserver::class);
        }
    }
}
