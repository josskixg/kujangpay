<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * KujangPay Bank & Merchant Mutation Parser + Exact Nominal Auto-Matcher.
 * Parses raw notification payloads from BCA, Livin' by Mandiri, BRImo, GoPay Merchant,
 * Dana Bisnis, or IMAP email alerts and reconciles against active PENDING invoices.
 */
class MutationMatcherService
{
    /**
     * Parse raw Indonesian bank / QRIS merchant notification text and extract
     * nominal IDR amount + detected channel source.
     *
     * Examples supported:
     * - "BCA: QRIS Kredit Rp 150.127,00 dari JOHN DOE"
     * - "Mandiri: Transfer masuk IDR 75,123.00 ke rek 123456"
     * - "GoPay Merchant: Pembayaran QRIS Rp150.088 berhasil diterima"
     * - "BRI Notifikasi: Kredit Rp 240.089 pada 09/11/2026"
     *
     * @return array{amount: int, channel: string, raw: string}
     */
    public static function parseNotification(string $rawNotification): array
    {
        $clean = trim($rawNotification);
        if ($clean === '') {
            throw new InvalidArgumentException('Notification string cannot be empty.');
        }

        $channel = 'GENERIC_QRIS';
        $upper = strtoupper($clean);
        foreach (['BCA', 'MANDIRI', 'BRI', 'BNI', 'BSI', 'GOPAY', 'DANA', 'SHOPEEPAY', 'OVO'] as $bank) {
            if (str_contains($upper, $bank)) {
                $channel = $bank;
                break;
            }
        }

        // Match currency prefix Rp / IDR followed by formatted digits, with optional decimals (,00 or .00)
        if (!preg_match('/(?:RP|IDR)\.?\s*([0-9][0-9\.,]*)/i', $clean, $matches)) {
            throw new InvalidArgumentException("Unable to extract IDR nominal from notification: {$clean}");
        }

        $numericToken = $matches[1];
        // Strip trailing 2-digit decimal cents like ,00 or .00
        $numericToken = preg_replace('/[\.,][0-9]{2}$/', '', $numericToken) ?? $numericToken;
        // Strip all thousand separators (. or ,)
        $digitsOnly = preg_replace('/[^0-9]/', '', $numericToken) ?? '';

        if ($digitsOnly === '' || (int) $digitsOnly <= 0) {
            throw new InvalidArgumentException("Extracted invalid nominal from notification: {$clean}");
        }

        return [
            'amount'  => (int) $digitsOnly,
            'channel' => $channel,
            'raw'     => $clean,
        ];
    }

    /**
     * Match extracted mutation amount against active PENDING invoices within expiry window.
     *
     * @param array<int, array<string, mixed>> $pendingInvoices
     * @return array<string, mixed>|null
     */
    public static function matchAndSettle(
        string $rawNotification,
        array $pendingInvoices,
        string $webhookSecret,
        ?int $nowTimestamp = null
    ): ?array {
        $parsed = self::parseNotification($rawNotification);
        $now = $nowTimestamp ?? time();

        foreach ($pendingInvoices as $invoice) {
            $status = strtoupper((string) ($invoice['status'] ?? 'PENDING'));
            if ($status !== 'PENDING') {
                continue;
            }

            $totalAmount = (int) ($invoice['total_amount'] ?? 0);
            if ($totalAmount !== $parsed['amount']) {
                continue;
            }

            if (isset($invoice['expires_at'])) {
                $expiresAt = is_int($invoice['expires_at'])
                    ? $invoice['expires_at']
                    : strtotime((string) $invoice['expires_at']);
                if ($expiresAt !== false && $expiresAt < $now) {
                    continue;
                }
            }

            $webhookPayload = [
                'event'        => 'invoice.settled',
                'invoice_ref'  => (string) ($invoice['invoice_ref'] ?? ''),
                'merchant'     => (string) ($invoice['merchant'] ?? 'MERCHANT'),
                'base_amount'  => (int) ($invoice['base_amount'] ?? $totalAmount),
                'unique_code'  => (int) ($invoice['unique_code'] ?? 0),
                'total_amount' => $totalAmount,
                'crc16'        => (string) ($invoice['crc16'] ?? ''),
                'channel'      => $parsed['channel'],
                'status'       => 'PAID',
                'settled_at'   => gmdate('Y-m-d\TH:i:s\Z', $now),
            ];

            $signature = QrisMutatorService::signWebhook($webhookPayload, $webhookSecret);

            return [
                'matched'           => true,
                'invoice_ref'       => $webhookPayload['invoice_ref'],
                'channel'           => $parsed['channel'],
                'total_amount'      => $totalAmount,
                'status'            => 'PAID',
                'webhook_payload'   => $webhookPayload,
                'webhook_signature' => $signature,
            ];
        }

        return null;
    }
}
