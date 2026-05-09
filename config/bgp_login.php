<?php

return [
    /*
     |---------------------------------------------------------------------------
     | Login institucional (Filament Admin)
     |---------------------------------------------------------------------------
     | Caminhos relativos a /public (sem leading slash).
     */

    'pgtrust' => [
        'logo' => env('BGP_LOGIN_PGTRUST_LOGO', null),
        'brand_background' => env('BGP_LOGIN_PGTRUST_BRAND_BG', 'images/login/pgtrust-brand-bg.png'),
    ],

    'governance2u' => [
        'logo' => env('BGP_LOGIN_GOVERNANCE_LOGO', null),
    ],
];

