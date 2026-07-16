<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz M5: ürün ilanlarına opsiyonel boyut (en/yükseklik, cm). "Boyut
 * karşılaştır" görseli (x-size-compare) bunları 170cm insan silüetiyle
 * ölçekli kıyaslar. Yalnızca ürün tipinde doldurulur; boşsa özellik gizli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedSmallInteger('width_cm')->nullable()->after('stock');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('width_cm');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['width_cm', 'height_cm']);
        });
    }
};
