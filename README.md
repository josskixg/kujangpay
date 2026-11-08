<p align="center">
  <img src="logo.svg" width="112" height="112" alt="KujangPay Logo">
</p>

<h1 align="center">KujangPay — Self-Hosted Dynamic EMVCo QRIS & Webhook Engine for Laravel 11</h1>

<p align="center">
  <strong>Zero Third-Party MDR Fee • ISO/IEC 13239 CRC16-CCITT • HMAC-SHA256 Settlement Webhooks • Linear Dark-Mode Console</strong>
</p>

---

## Mengapa KujangPay? (High-Value Commercial Boilerplate)
UMKM dan developer SaaS di Indonesia rata-rata kehilangan **0.7% hingga Rp 4.000 per transaksi** akibat biaya payment gateway pihak ketiga. **KujangPay** mengubah string **QRIS Statis (`Tag 01 = 11`)** milik merchant menjadi **QRIS Dinamis (`Tag 01 = 12`)** secara on-the-fly dengan menyuntikkan nominal tagihan + kode unik verifikasi otomatis ke **`Tag 54`**, nomor referensi invoice ke **`Tag 62`**, serta menghitung ulang checksum **`Tag 63` (CRC16-CCITT polynomial `0x1021`)**.

### Live Operator Console (GitHub Pages)
- **Live Console:** [https://josskixg.github.io/kujangpay/](https://josskixg.github.io/kujangpay/)

---

## Struktur Arsitektur (Laravel 11 / PHP 8.2+)
```text
kujangpay/
├── DESIGN.md                                         # Formal Google DESIGN.md Spec (Linear Dark System)
├── logo.svg                                          # Audited Geometric Brand Mark (Score: 100/100)
├── assets/brand/                                     # Full Exported Icon Kit (Favicon, App Icons, Mono)
├── app/
│   ├── Services/QrisMutatorService.php               # EMVCo TLV Parser, Dynamic Mutator, CRC16, HMAC
│   └── Http/Controllers/PaymentGatewayController.php # Laravel 11 Invoice & Webhook Endpoint
├── database/migrations/                              # Schema kujangpay_invoices
├── routes/api.php                                    # REST API v1/kujangpay routes
├── tests/Unit/QrisMutatorTest.php                    # Self-contained PHP 8.2 Unit Test Suite
└── index.html                                        # Linear-Styled Monitor & Operate Live Console
```

## Menjalankan Unit Test (PHP 8.2+)
```bash
php tests/Unit/QrisMutatorTest.php
```
