<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İlan metninde dolandırıcılık deseni işareti.
 *
 * TEK SÜTUN, BOOLEAN + SEBEP DEĞİL: `fraud_reason` null ise temiz, doluysa
 * işaretli. İki sütun olsaydı "işaretli ama sebebi yok" ya da "sebebi var ama
 * işaretli değil" gibi tutarsız bir hâl mümkün olurdu; görsel tarafında
 * (`is_flagged` + `flagged_reason`) o risk zaten var.
 *
 * `fraud_checked_at` ayrı: "denetlendi ve temiz" ile "hiç denetlenmedi" aynı
 * şey değil. Panelde "hiç bakılmamış ilan" sorusu ancak bu sütunla sorulabilir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('fraud_reason', 40)->nullable()->after('status');
            $table->timestamp('fraud_checked_at')->nullable()->after('fraud_reason');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['fraud_reason', 'fraud_checked_at']);
        });
    }
};
