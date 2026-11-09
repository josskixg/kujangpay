<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/Services/QrisMutatorService.php';
require_once __DIR__ . '/../../app/Services/MutationMatcherService.php';
require_once __DIR__ . '/../../app/Http/Controllers/PaymentGatewayController.php';

use App\Services\QrisMutatorService;
use App\Services\MutationMatcherService;
use App\Http\Controllers\PaymentGatewayController;

// 0. Verify Laravel 11 Config File
$config = require __DIR__ . '/../../config/kujangpay.php';
assert(is_array($config), 'Config must return an array');
assert($config['unique_code']['min'] === 1 && $config['unique_code']['max'] === 499, 'Unique code range must be 1..499');
assert($config['expiry_minutes'] === 15, 'Default expiry must be 15 minutes');

// Sample valid EMVCo Static QRIS (Merchant: KEDAI KUJANG, City: SERANG)
$sampleStaticQris = $config['static_qris'];

// 1. Verify TLV Parser
$parsed = QrisMutatorService::parseTlv($sampleStaticQris);
assert($parsed['00'] === '01', 'Tag 00 must be 01');
assert($parsed['01'] === '11', 'Static QRIS Tag 01 must be 11');
assert($parsed['59'] === 'KEDAI KUJANG', 'Merchant name must match');
assert($parsed['60'] === 'SERANG', 'Merchant city must match');

// 2. Verify Dynamic Mutation (Nominal Rp 75.000 + Unique Code 123 = 75123)
$mutated = QrisMutatorService::generateDynamic($sampleStaticQris, 75000, 123, 'INV-2026-0001');
assert($mutated['total_amount'] === 75123, 'Total amount must include unique code');
assert(strlen($mutated['crc16']) === 4, 'CRC16 must be 4 hex chars');

$reparsed = QrisMutatorService::parseTlv($mutated['qris_string']);
assert($reparsed['01'] === '12', 'Mutated QRIS Tag 01 must be 12 (Dynamic)');
assert($reparsed['54'] === '75123', 'Tag 54 must equal exact total_amount');
assert($reparsed['63'] === $mutated['crc16'], 'Embedded Tag 63 must match computed CRC16');

// Verify CRC16 recalculation over payload body
$withoutCrcVal = substr($mutated['qris_string'], 0, -4);
assert(QrisMutatorService::crc16($withoutCrcVal) === $mutated['crc16'], 'CRC16-CCITT verification failed');

// 3. Verify Controller + HMAC-SHA256 Webhook Signing
$controller = new PaymentGatewayController();
$invoice = $controller->createInvoice(
    ['amount' => 150000, 'unique_code' => 88, 'invoice_ref' => 'INV-2026-8888'],
    $sampleStaticQris,
    'whsec_kujang_2026_secret'
);
assert($invoice['data']['total_amount'] === 150088, 'Controller invoice total mismatch');
assert(strlen($invoice['signature']) === 64, 'HMAC-SHA256 signature must be 64 hex characters');

// 4. Verify MutationMatcherService across BCA, Mandiri, BRI, and GoPay Merchant notifications
$bca = MutationMatcherService::parseNotification('BCA: QRIS Kredit Rp 150.088,00 dari BUDI SANTOSO');
assert($bca['amount'] === 150088 && $bca['channel'] === 'BCA', 'BCA notification parse failed');

$mandiri = MutationMatcherService::parseNotification("Livin' by Mandiri: Transfer masuk IDR 75,123.00 berhasil");
assert($mandiri['amount'] === 75123 && $mandiri['channel'] === 'MANDIRI', 'Mandiri notification parse failed');

$gopay = MutationMatcherService::parseNotification('GoPay Merchant: Pembayaran QRIS Rp240.319 diterima');
assert($gopay['amount'] === 240319 && $gopay['channel'] === 'GOPAY', 'GoPay notification parse failed');

// Verify Auto-Matcher & Settlement Webhook Signing within active expiry window
$now = 1762680000;
$pendingPool = [
    [
        'invoice_ref'  => 'INV-2026-EXPIRED',
        'merchant'     => 'KEDAI KUJANG',
        'base_amount'  => 150000,
        'unique_code'  => 88,
        'total_amount' => 150088,
        'crc16'        => $invoice['data']['crc16'],
        'status'       => 'PENDING',
        'expires_at'   => $now - 60, // already expired
    ],
    [
        'invoice_ref'  => 'INV-2026-8888',
        'merchant'     => 'KEDAI KUJANG',
        'base_amount'  => 150000,
        'unique_code'  => 88,
        'total_amount' => 150088,
        'crc16'        => $invoice['data']['crc16'],
        'status'       => 'PENDING',
        'expires_at'   => $now + 900, // active window
    ],
];

$matched = MutationMatcherService::matchAndSettle(
    'BCA: QRIS Kredit Rp 150.088,00 dari PELANGGAN QRIS',
    $pendingPool,
    'whsec_kujang_2026_secret',
    $now
);
assert($matched !== null && $matched['matched'] === true, 'Active invoice must match');
assert($matched['invoice_ref'] === 'INV-2026-8888', 'Must skip expired invoice and match active invoice');
assert($matched['status'] === 'PAID', 'Settled status must be PAID');
assert(strlen($matched['webhook_signature']) === 64, 'Settlement HMAC-SHA256 signature must be 64 hex chars');

// Verify Controller handleBankMutation endpoint
$controllerMatch = $controller->handleBankMutation(
    ['notification_text' => 'BRImo Notifikasi: Dana Masuk QRIS Rp 150.088'],
    [$pendingPool[1]],
    'whsec_kujang_2026_secret'
);
assert($controllerMatch['matched'] === true && $controllerMatch['channel'] === 'BRI', 'Controller BRI mutation match failed');

echo "PASS: PHP 8.2 / Laravel 11 QrisMutatorService + MutationMatcherService (EMVCo TLV + CRC16-CCITT + HMAC-SHA256) verified.\n";
