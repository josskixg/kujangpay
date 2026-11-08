<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QrisMutatorService;

/**
 * Laravel 11 API Controller for KujangPay Self-Hosted Gateway.
 */
class PaymentGatewayController
{
    public function createInvoice(array $request, string $staticQris, string $webhookSecret): array
    {
        $amount = (int) ($request['amount'] ?? 0);
        $uniqueCode = (int) ($request['unique_code'] ?? random_int(1, 499));
        $invoiceRef = (string) ($request['invoice_ref'] ?? ('INV-' . date('Ymd') . '-' . random_int(1000, 9999)));

        $mutated = QrisMutatorService::generateDynamic($staticQris, $amount, $uniqueCode, $invoiceRef);

        $webhookPayload = [
            'event'        => 'invoice.created',
            'invoice_ref'  => $invoiceRef,
            'total_amount' => $mutated['total_amount'],
            'crc16'        => $mutated['crc16'],
            'status'       => 'PENDING',
            'timestamp'    => date(DATE_ATOM),
        ];

        return [
            'data'      => $mutated,
            'signature' => QrisMutatorService::signWebhook($webhookPayload, $webhookSecret),
        ];
    }
}
