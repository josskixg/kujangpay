<p align="center">
  <img src="logo.svg" width="112" height="112" alt="KujangPay Logo">
</p>

<h1 align="center">KujangPay — Self-Hosted Dynamic EMVCo QRIS & Webhook Engine for Laravel 11</h1>

<p align="center">
  <strong>Zero 0% MDR Gateway Fee • ISO/IEC 13239 CRC16-CCITT • Auto-Match Bank Mutation • HMAC-SHA256 Webhooks</strong>
</p>

<p align="center">
  <a href="https://josskixg.github.io/kujangpay/"><img src="https://img.shields.io/badge/Live_Console-Linear_Dark_%2F_Light-5E6AD2?style=for-the-badge" alt="Live Console"></a>
  <img src="https://img.shields.io/badge/PHP-8.2%2B-10B981?style=for-the-badge" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-11.x-F59E0B?style=for-the-badge" alt="Laravel 11">
  <img src="https://img.shields.io/badge/SVG_Audit-100%2F100-10B981?style=for-the-badge" alt="SVG Audit 100/100">
</p>

---

## 1. Masalah & Nilai Jual Komersial (Commercial Value Proposition)

UMKM, pemilik toko digital, dan developer micro-SaaS di Indonesia rata-rata kehilangan **0.7% MDR hingga Rp 4.500 per transaksi** serta harus menunggu proses settlement H+1 hingga H+2 dari aggregator pihak ketiga.

**KujangPay** adalah paket *Self-Hosted Payment Gateway* untuk **Laravel 11 / PHP 8.2+** yang memproses **QRIS Statis (`Tag 01 = 11`)** milik merchant menjadi **QRIS Dinamis (`Tag 01 = 12`)** secara instan:
1. Menyuntikkan **Nominal Tagihan + Kode Unik Verifikasi (`Tag 54`)** dan **Nomor Referensi Invoice (`Tag 62.01`)**.
2. Menghitung ulang checksum 16-bit **`Tag 63` (`ISO/IEC 13239 CRC16-CCITT`, polynomial `0x1021`, initial `0xFFFF`)**.
3. Mencocokkan notifikasi mutasi bank/merchant secara otomatis (`MutationMatcherService`) dan mengirim webhook bersegel **`HMAC-SHA256`**.

| Parameter Komparasi | Aggregator Pihak Ketiga | **KujangPay (Self-Hosted)** |
| :--- | :--- | :--- |
| **Biaya per Transaksi** | 0.7% MDR / Rp 2.500 – Rp 4.500 | **Rp 0 (100% Bebas Potongan)** |
| **Aliran Dana (Settlement)** | Mengendap H+1 / H+2 di Aggregator | **Detik Itu Juga Masuk Rekening Sendiri** |
| **Kepatuhan Standar QRIS** | Proprietary API | **EMVCo MPM v1.1 + ISO/IEC 13239 CRC16** |
| **Keamanan Callback** | Shared Token | **Replay-Protected HMAC-SHA256 Signature** |
| **UI Operator Console** | Dashboard Standar | **Linear Dark/Light Console (`DESIGN.md`)** |

---

## 2. Live Interactive Operator Console
Uji langsung konversi QRIS Statis ke Dinamis, kalkulasi `CRC16-CCITT`, simulasi pencocokan mutasi bank, dan fitur **Dark/Light Mode**:
- **URL Live Demo:** [https://josskixg.github.io/kujangpay/](https://josskixg.github.io/kujangpay/)

---

## 3. Struktur Folder Laravel 11 / PHP 8.2
```text
kujangpay/
├── DESIGN.md                                         # Google DESIGN.md Spec (Linear Dark & Light Tokens)
├── composer.json                                     # PSR-4 Autoload & PHP 8.2 Package Definition
├── logo.svg                                          # Geometric Brand Mark (SVG Audit: 100/100)
├── assets/brand/                                     # 17 Production Icons (Favicon, Apple Touch, PWA, Mono)
├── config/
│   └── kujangpay.php                                 # Static QRIS, Unique Code Pool, & Webhook Config
├── app/
│   ├── Services/
│   │   ├── QrisMutatorService.php                    # EMVCo TLV Parser, Tag 54/62 Mutator, CRC16 & HMAC
│   │   └── MutationMatcherService.php                # Bank/Merchant Notification Parser & Auto-Matcher
│   └── Http/Controllers/
│       └── PaymentGatewayController.php              # Invoice Generator & Mutation Webhook Endpoint
├── database/migrations/
│   └── 2026_11_09_000001_create_kujangpay_invoices_table.php
├── routes/
│   └── api.php                                       # /api/v1/kujangpay REST Endpoints
├── tests/Unit/
│   └── QrisMutatorTest.php                           # PHP 8.2 Unit Test Suite (TLV, CRC16, Matcher, HMAC)
└── index.html                                        # Responsive Linear Dark/Light Operator Console
```

---

## 4. Anatomi EMVCo TLV yang Dimutasi
| Tag | Nama Field EMVCo | Nilai Statis | Nilai Dinamis (KujangPay) |
| :---: | :--- | :--- | :--- |
| `00` | Payload Format Indicator | `01` | `01` |
| `01` | Point of Initiation Method | `11` (Statis) | **`12` (Dinamis)** |
| `26` | Merchant Account Information | NMID & Acquirer ID | Tetap utuh (Zero Tamper) |
| `53` | Transaction Currency | `360` (IDR) | `360` (IDR) |
| `54` | Transaction Amount | *Tidak Ada* | **Nominal + Kode Unik (misal `150127`)** |
| `62` | Additional Data Field | *Opsional* | **`01` + Panjang + `INV-2026-XXXX`** |
| `63` | Cyclic Redundancy Check | Checksum Lama | **Checksum Baru (`CRC16-CCITT 0x1021`)** |

---

## 5. Cara Menjalankan Unit Test
```bash
php tests/Unit/QrisMutatorTest.php
# Output: PASS: PHP 8.2 / Laravel 11 QrisMutatorService + MutationMatcherService (EMVCo TLV + CRC16-CCITT + HMAC-SHA256) verified.
```
