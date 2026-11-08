<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/Services/QrisMutatorService.php';
require_once __DIR__ . '/../../app/Http/Controllers/PaymentGatewayController.php';

use App\Services\QrisMutatorService;
use App\Http\Controllers\PaymentGatewayController;

// Sample valid EMVCo Static QRIS (Merchant: KEDAI KUJANG, City: SERANG)
$sampleStaticQris = '00020101021126600014ID.CO.QRIS.WWW011893600523000000012302091234567890303UMI5204581253033605802ID5912KEDAI KUJANG6006SERANG6304A1B2';

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

echo "PASS: PHP 8.2 / Laravel 11 QrisMutatorService (EMVCo TLV + CRC16-CCITT + HMAC-SHA256) verified.\n";
