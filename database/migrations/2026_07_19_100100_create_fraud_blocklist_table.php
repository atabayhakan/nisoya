<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_blocklist', function (Blueprint $table) {
            $table->id();
            // type: email | iban | payment_handle | ip
            $table->string('type', 20);
            // Değer DÜZ saklanmaz — normalize edilip HMAC-SHA256 ile hash'lenir
            // (GDPR: dondurulan kişinin PII'sini sonsuza dek düz tutma; eşleşme
            // için exact-match yeterli). hex sha256 = 64 karakter.
            $table->string('value_hash', 64);
            $table->string('reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['type', 'value_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_blocklist');
    }
};
