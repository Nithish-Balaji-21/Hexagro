@php
    $navSections = [
        'Overview' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
        ],
        'Transactions' => [
            ['id' => 'debit', 'label' => 'Debit', 'route' => 'debit', 'icon' => 'down'],
            ['id' => 'credit', 'label' => 'Credit', 'route' => 'credit', 'icon' => 'up'],
            ['id' => 'transfers', 'label' => 'Transfers', 'route' => 'transfers', 'icon' => 'scale'],
        ],
        'Reports' => [
            ['id' => 'monthlySpend', 'label' => 'Monthly Spend', 'route' => 'monthly-spend', 'icon' => 'calendar'],
            ['id' => 'settlement', 'label' => 'Summary & Settlement', 'route' => 'settlement', 'icon' => 'scale'],
            ['id' => 'purchases', 'label' => 'Purchases', 'route' => 'purchases', 'icon' => 'down'],
            ['id' => 'sales', 'label' => 'Sales', 'route' => 'sales', 'icon' => 'trend'],
        ],
        'Finance' => [
            ['id' => 'banking', 'label' => 'Banking', 'route' => 'banking', 'icon' => 'bank'],
            ['id' => 'entityLedgers', 'label' => 'Ledger Book', 'route' => 'ledger-book', 'icon' => 'history'],
            ['id' => 'historicalAlam', 'label' => 'Historical Alam Expenses', 'route' => 'historical-alam', 'icon' => 'history'],
        ],
    ];

    $currentPage = $currentPage ?? 'dashboard';
    $pageTitle = $pageTitle ?? 'Dashboard';
@endphp
