<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "İlan ipuçları" bildirimi bu ilan için gönderildi mi?
 *
 * TEK DAMGA, SAYAÇ DEĞİL: bildirim ilan başına BİR KEZ gönderiliyor. Sayaç
 * ya da "son gönderim" tarihi tutsaydık, kaçınılmaz olarak "30 günde bir
 * tekrar" gibi bir kural eklenirdi. Görmezden gelinen bir öneriyi tekrar
 * etmek, insanın ilanı düzeltmesini değil bildirimleri kapatmasını sağlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('tips_notified_at')->nullable()->after('fraud_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('tips_notified_at');
        });
    }
};
