<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F3: harcama defteri artık LLM dışındaki dış çağrıları da (web araması,
 * işletme keşfi) sayıyor. `detay` çağrının ne olduğunu taşır (arama sorgusu
 * gibi) — panelde "neye harcandı" sorusunun cevabı ve F5 ders-cikar'ın
 * hammaddesi. LLM satırlarında boş kalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kahya_harcamalar', function (Blueprint $table) {
            $table->string('detay', 200)->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('kahya_harcamalar', function (Blueprint $table) {
            $table->dropColumn('detay');
        });
    }
};
