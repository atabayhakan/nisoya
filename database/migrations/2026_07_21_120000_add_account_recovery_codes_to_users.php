<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hesap kurtarma kodları — parola VE e-posta erişimi birlikte kaybedildiğinde
 * (ör. SMTP çalışmıyorken parola unutulduğunda) e-postaya ihtiyaç duymadan
 * parola sıfırlamayı sağlar. 2FA yedek kodlarından farklıdır: onlar paroladan
 * sonra TOTP'yi atlar; bunlar parolanın kendisini kurtarır.
 * Bkz. [[nisoya-admin-panel-plani]] Faz 1 · G2 (Kurtarma Kiti).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // bcrypt hash'lerin JSON dizisi; kolon ayrıca 'encrypted:array' ile
            // dinlenirken de şifrelenir (bkz. User $casts).
            $table->text('account_recovery_codes')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_recovery_codes');
        });
    }
};
