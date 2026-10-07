<?php

/**
 * Konfigurasi dashboard proaktif.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Target PNPP
    |--------------------------------------------------------------------------
    | Angka default ada di DashboardController::TARGET_PNPP. Isi env ini hanya
    | jika ingin menimpa default tersebut tanpa mengubah kode.
    */
    'target_pnpp' => env('DASHBOARD_TARGET_PNPP'),

    /*
    |--------------------------------------------------------------------------
    | Target capaian monitoring 60 hari
    |--------------------------------------------------------------------------
    | Ambang minimal (%) yang membuat baris berstatus "On Track".
     */
    'monitoring' => [
        'outreach' => (int) env('DASHBOARD_TARGET_OUTREACH', 65),
        'respon' => (int) env('DASHBOARD_TARGET_RESPON', 53),
        'digital_reminder' => (int) env('DASHBOARD_TARGET_REMINDER', 52),
        'followup' => (int) env('DASHBOARD_TARGET_FOLLOWUP', 92),
        'kunjungan' => (int) env('DASHBOARD_TARGET_KUNJUNGAN', 15),
    ],
];
