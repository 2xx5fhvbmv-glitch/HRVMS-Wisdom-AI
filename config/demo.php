<?php

/*
| Demo ENV — one demo resort inside the normal database, rebuilt on demand.
| Everything demo-only (reset, email redirect, relaxed demo gates) applies to
| this one resort and only when DEMO_MODE=true.
*/
return [
    'enabled'      => (bool) env('DEMO_MODE', false),

    // The demo resort is found by this code (resorts.resort_id) — never by name,
    // because the super admin renames it and swaps the logo for each client demo.
    'resort_code'  => env('DEMO_RESORT_CODE', 'DEMOENV001'),
    'resort_name'  => 'Demo ENV',
    'resort_email' => 'resort@demo.thewisdom.ai',
    'prefix'       => 'DEMO', // Employee IDs: DEMO-0001…

    'login_domain' => env('DEMO_LOGIN_DOMAIN', 'demo.thewisdom.ai'),
    // One password for every demo login, set at reset. Never committed.
    'password'     => env('DEMO_PASSWORD'),

    // Every email the demo resort would send goes here instead (with an
    // "Intended for" banner). Nothing is ever delivered to anyone else.
    'mail_to'      => env('DEMO_MAIL_TO', 'amey.tamshetti@gmail.com'),

    // Refuse to purge a resort larger than this — a real resort is never that small
    // by accident, and the demo is ~100 employees.
    'max_employees' => 400,
];
