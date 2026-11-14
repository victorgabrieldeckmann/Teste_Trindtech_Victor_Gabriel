<?php

return [
    'base_url' => env(
        'SGS_BASE_URL',
        'https://api.bcb.gov.br/dados/serie/bcdata.sgs',
    ),

    'start_date' => '2025-01-01',
    'end_date' => '2026-11-01',
    'timeout' => (int) env('SGS_TIMEOUT', 15),
    'seed_mode' => env('SGS_SEED_MODE', 'online'),
];
