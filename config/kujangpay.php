<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Raw Merchant Static QRIS String (EMVCo Tag 01 = 11)
    |--------------------------------------------------------------------------
    | Extracted once from your physical or digital QRIS print-out (BCA, Mandiri,
    | BRI, GoPay Merchant, Dana Bisnis, or ShopeePay Merchant).
    */
    'static_qris' => function_exists('env')
        ? env('KUJANGPAY_STATIC_QRIS', '00020101021126600014ID.CO.QRIS.WWW011893600523000000012302091234567890303UMI5204581253033605802ID5912KEDAI KUJANG6006SERANG6304A1B2')
        : '00020101021126600014ID.CO.QRIS.WWW011893600523000000012302091234567890303UMI5204581253033605802ID5912KEDAI KUJANG6006SERANG6304A1B2',

    /*
    |--------------------------------------------------------------------------
    | Unique Verification Code Range (Tag 54 Collision Guard)
    |--------------------------------------------------------------------------
    | Appended to base_amount so incoming bank mutations with identical base
    | prices can be deterministically matched in O(1) without 3rd-party MDR.
    */
    'unique_code' => [
        'enabled' => true,
        'min'     => 1,
        'max'     => 499,
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Expiry Window (Minutes)
    |--------------------------------------------------------------------------
    | Unpaid dynamic QRIS invoices expire after this window, releasing their
    | unique_code back into the active pool.
    */
    'expiry_minutes' => function_exists('env') ? (int) env('KUJANGPAY_EXPIRY_MINUTES', 15) : 15,

    /*
    |--------------------------------------------------------------------------
    | Outbound & Inbound Webhook Security (HMAC-SHA256)
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'secret'           => function_exists('env') ? env('KUJANGPAY_WEBHOOK_SECRET', 'whsec_kujang_2026_secret') : 'whsec_kujang_2026_secret',
        'signature_header' => 'X-KujangPay-Signature',
        'tolerance_sec'    => 300,
        'retry_policy'     => [
            'max_attempts'       => 5,
            'backoff_Preset_sec' => [5, 15, 60, 300, 900],
        ],
    ],
];
