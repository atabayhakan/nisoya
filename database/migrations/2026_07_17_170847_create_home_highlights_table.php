<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ana sayfa "değer önerileri" bento grid'indeki büyük (2x2) ve küçük
     * (1x1) kartların içeriği — admin panelden yönetilen, otomatik dönen
     * mesaj listesi (bkz. docs/plans/2026-07-17-anasayfa-vurgu-slider-design.md).
     * `slot` hangi kartta gösterileceğini belirler; tek tablo, admin
     * panelde iki ayrı (slot'a göre filtrelenmiş) kaynak olarak sunulur.
     */
    public function up(): void
    {
        Schema::create('home_highlights', function (Blueprint $table) {
            $table->id();
            $table->string('slot');
            $table->string('title');
            $table->string('text');
            $table->string('icon');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['slot', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_highlights');
    }
};
