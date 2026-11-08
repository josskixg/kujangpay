<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kujangpay_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_ref', 32)->unique()->index();
            $table->string('merchant_name', 120);
            $table->unsignedBigInteger('base_amount');
            $table->unsignedSmallInteger('unique_code')->default(0);
            $table->unsignedBigInteger('total_amount')->index();
            $table->text('qris_payload');
            $table->char('crc16', 4);
            $table->enum('status', ['PENDING', 'PAID', 'EXPIRED'])->default('PENDING')->index();
            $table->string('webhook_signature', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kujangpay_invoices');
    }
};
