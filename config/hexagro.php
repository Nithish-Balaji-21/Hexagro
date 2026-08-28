<?php

return [

    'fiscal_year_start_month' => 4,

    'overall_scope' => 'Overall',

    'fibre_unit_name' => 'Fibre Unit',

    'alam_attribution' => [
        'jagadeesan' => 0.5,
        'jagadeshwaran' => 0.5,
        'vellingiri' => 0,
        'vikas' => 0,
    ],

    'ubi_participants' => [
        'Fibre Unit' => ['jagadeesan', 'jagadeshwaran', 'vellingiri'],
        'Chips Unit' => ['jagadeesan', 'jagadeshwaran', 'vellingiri'],
        'Washing Unit' => ['jagadeesan', 'jagadeshwaran', 'vellingiri'],
    ],

    'hist_alam_share_pct' => 0.66666,

    'unit_shareholders' => [
        'Fibre Unit' => ['jagadeesan', 'jagadeshwaran', 'vellingiri', 'vikas'],
        'Chips Unit' => ['jagadeesan', 'jagadeshwaran', 'vellingiri'],
        'Washing Unit' => ['jagadeesan', 'jagadeshwaran', 'vellingiri'],
    ],

    'entity_keys' => [
        'Shareholder - Jagadeesan' => 'jagadeesan',
        'Shareholder - Jagadeshwaran' => 'jagadeshwaran',
        'Shareholder - Vellingiri' => 'vellingiri',
        'Vikas' => 'vikas',
    ],

    'settlement_balanced_tolerance' => 1.00,

    'suggested_transfer_threshold' => 0.5,

];
