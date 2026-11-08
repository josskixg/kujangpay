<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * KujangPay EMVCo QRIS Static-to-Dynamic Mutator & CRC16-CCITT (0xFFFF) Engine.
 * Compatible with Laravel 11 / PHP 8.2+ without external composer packages.
 */
class QrisMutatorService
{
    /**
     * Compute ISO/IEC 13239 CRC16-CCITT (polynomial 0x1021, initial 0xFFFF)
     * as mandated by Bank Indonesia QRIS / EMVCo Merchant Presented Mode v1.1.
     */
    public static function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($bit = 0; $bit < 8; $bit++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Parse raw EMVCo QRIS TLV string into associative array of [tag => value].
     *
     * @return array<string, string>
     */
    public static function parseTlv(string $qris): array
    {
        $qris = trim($qris);
        $index = 0;
        $len = strlen($qris);
        $tags = [];

        while ($index + 4 <= $len) {
            $tag = substr($qris, $index, 2);
            $valLenStr = substr($qris, $index + 2, 2);
            if (!ctype_digit($valLenStr)) {
                throw new InvalidArgumentException("Invalid TLV length at index {$index}");
            }
            $valLen = (int) $valLenStr;
            $value = substr($qris, $index + 4, $valLen);
            $tags[$tag] = $value;
            $index += 4 + $valLen;
        }

        return $tags;
    }

    /**
     * Convert Static QRIS (Tag 01 = 11) to Dynamic QRIS (Tag 01 = 12),
     * inject exact Nominal + Unique Verification Code (Tag 54), optional Invoice Reference (Tag 62),
     * and recalculate Tag 63 (CRC16-CCITT).
     */
    public static function generateDynamic(
        string $staticQris,
        int $baseAmount,
        int $uniqueCode = 0,
        string $invoiceRef = ''
    ): array {
        if ($baseAmount <= 0) {
            throw new InvalidArgumentException('Base amount must be greater than zero.');
        }

        $tags = self::parseTlv($staticQris);
        if (!isset($tags['00']) || !isset($tags['58'])) {
            throw new InvalidArgumentException('Invalid EMVCo QRIS string: missing mandatory tags 00 or 58.');
        }

        unset($tags['63']); // Strip old CRC

        $totalAmount = $baseAmount + max(0, $uniqueCode);
        $tags['01'] = '12'; // 11 = Static, 12 = Dynamic
        $tags['54'] = (string) $totalAmount;

        if ($invoiceRef !== '') {
            $cleanRef = substr(preg_replace('/[^A-Za-z0-9\-]/', '', $invoiceRef) ?? '', 0, 20);
            if ($cleanRef !== '') {
                $subTag01 = '01' . str_pad((string) strlen($cleanRef), 2, '0', STR_PAD_LEFT) . $cleanRef;
                $tags['62'] = $subTag01;
            }
        }

        // Preserve canonical EMVCo tag order: 00, 01, 26..51, 52, 53, 54, 55..62
        ksort($tags, SORT_STRING);

        $body = '';
        foreach ($tags as $tag => $val) {
            $body .= $tag . str_pad((string) strlen($val), 2, '0', STR_PAD_LEFT) . $val;
        }

        $payloadWithCrcHeader = $body . '6304';
        $crc = self::crc16($payloadWithCrcHeader);
        $finalQris = $payloadWithCrcHeader . $crc;

        return [
            'qris_string' => $finalQris,
            'crc16'       => $crc,
            'base_amount' => $baseAmount,
            'unique_code' => $uniqueCode,
            'total_amount'=> $totalAmount,
            'merchant'    => $tags['59'] ?? 'UNKNOWN MERCHANT',
            'city'        => $tags['60'] ?? 'INDONESIA',
            'invoice_ref' => $invoiceRef,
        ];
    }

    /**
     * Sign webhook payload using HMAC-SHA256 for secure settlement callback verification.
     */
    public static function signWebhook(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return hash_hmac('sha256', $json, $secret);
    }
}
