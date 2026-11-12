<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shipment Numbering & Delay Configuration
    |--------------------------------------------------------------------------
    */
    'shipment_prefix' => env('CARGOTRACE_SHIPMENT_PREFIX', 'KLX'),

    'delay_threshold_hours' => (int) env('CARGOTRACE_DELAY_THRESHOLD_HOURS', 12),

    'delay_severity_thresholds' => [
        'low_max_hours' => 6,
        'medium_max_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipment Lifecycle State Machine
    |--------------------------------------------------------------------------
    */
    'status_transitions' => [
        'CREATED' => ['BOOKED', 'CANCELLED'],
        'BOOKED' => ['PICKED_UP', 'CANCELLED'],
        'PICKED_UP' => ['IN_TRANSIT'],
        'IN_TRANSIT' => ['DELIVERED'],
        'DELIVERED' => [],
        'CANCELLED' => [],
    ],

    'statuses' => [
        'CREATED',
        'BOOKED',
        'PICKED_UP',
        'IN_TRANSIT',
        'DELIVERED',
        'CANCELLED',
    ],

    /*
    |--------------------------------------------------------------------------
    | External Integrations (Open-Meteo Weather & Nominatim Geocoding)
    |--------------------------------------------------------------------------
    */
    'external_apis' => [
        'timeout_seconds' => (int) env('CARGOTRACE_EXTERNAL_API_TIMEOUT', 5),
        'open_meteo_url' => env('CARGOTRACE_OPEN_METEO_URL', 'https://api.open-meteo.com/v1/forecast'),
        'nominatim_url' => env('CARGOTRACE_NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'nominatim_user_agent' => env('CARGOTRACE_NOMINATIM_USER_AGENT', 'CargoTrace-KilatLogix/1.0 (ops@kilatlogix.test)'),
    ],
];
