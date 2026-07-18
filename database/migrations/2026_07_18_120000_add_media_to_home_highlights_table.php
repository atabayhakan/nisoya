<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Büyük/küçük vurgu kartlarına resim/galeri/video desteği ekler (bkz.
     * docs/plans/2026-07-18-buyuk-kart-medya-design.md). `media` doluysa
     * kartta `icon` yerine bu içerik gösterilir (App\Models\HomeHighlight).
     */
    public function up(): void
    {
        Schema::table('home_highlights', function (Blueprint $table) {
            $table->json('media')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('home_highlights', function (Blueprint $table) {
            $table->dropColumn('media');
        });
    }
};
