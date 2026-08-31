<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Finance\BankingIndex;
use App\Livewire\Finance\EntityLedgerIndex;
use App\Livewire\Import\ImportIndex;
use App\Livewire\Reports\MonthlySpendIndex;
use App\Livewire\Reports\PurchasesIndex;
use App\Livewire\Reports\SalesIndex;
use App\Livewire\Reports\SettlementIndex;
use App\Livewire\Transactions\CreditIndex;
use App\Livewire\Transactions\DebitIndex;
use App\Livewire\Transactions\TransferIndex;
use App\Services\Import\ImportTemplateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', Login::class)->name('login');
});

Route::middleware(['auth', 'unit.scope'])->group(function (): void {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/debit', DebitIndex::class)->name('debit');
    Route::get('/credit', CreditIndex::class)->name('credit');
    Route::get('/transfers', TransferIndex::class)->name('transfers');
    Route::get('/monthly-spend', MonthlySpendIndex::class)->name('monthly-spend');
    Route::get('/settlement', SettlementIndex::class)->name('settlement');
    Route::get('/purchases', PurchasesIndex::class)->name('purchases');
    Route::get('/sales', SalesIndex::class)->name('sales');
    Route::get('/banking', BankingIndex::class)->name('banking');
    Route::get('/ledger-book', EntityLedgerIndex::class)->name('ledger-book');
    Route::get('/import', ImportIndex::class)->name('import');
    Route::get('/import/template/{kind}', function (string $kind) {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return app(ImportTemplateService::class)->download($kind);
    })->name('import.template');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::post('/switch-user', function () {
        abort_unless(auth()->user()?->isAdmin(), 403);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('switch-user');
});
