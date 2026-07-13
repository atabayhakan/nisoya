<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Davetiye modülü çekirdeği (bkz. docs/plans/2026-07-13-emlak-vasita-davetiye-tasarim.md).
        // Tasarımdaki ayrı Invitation varlığı bilinçli olarak Event'e katlandı
        // (tema + metin alanları) — 1:1 tablo YAGNI.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // ev sahibi
            $table->string('type', 20);                  // EventType enum
            $table->string('title');                     // ör. "Ayşe & Mehmet Düğünü"
            $table->string('token', 24)->unique();       // tahmin edilemez davet linki
            $table->dateTime('starts_at');
            $table->string('venue_name')->nullable();    // ör. "Grand Salon"
            $table->string('venue_address')->nullable(); // düz metin adres
            $table->text('description')->nullable();     // davet metni
            $table->string('theme', 30);                 // config/event_themes.php anahtarı
            $table->boolean('is_active')->default(true); // kapatılınca davet linki 404
            $table->timestamps();

            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
