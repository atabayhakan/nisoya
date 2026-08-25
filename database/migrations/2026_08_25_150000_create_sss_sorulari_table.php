<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SSS (2026-08-25) — platformun kendi hakkındaki sorular, ÜLKEDEN VE
 * KATEGORİDEN BAĞIMSIZ düz liste (Yaşam Rehberi'nin 3 katmanlı modelinin
 * aksine: bugünkü 5 soru da ülkeye özgü değil, ayrı bir hiyerarşi gereksiz).
 *
 * `cevap` TEXT — VARCHAR DEĞİL: bu depoda MySQL strict mode'un VARCHAR'ı
 * sessizce kesmesi iki kez yaşandı (bkz. mysql-strict-varchar-tuzağı).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sss_sorulari', function (Blueprint $table) {
            $table->id();
            $table->string('soru', 300);
            $table->text('cevap');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sss_sorulari');
    }
};
