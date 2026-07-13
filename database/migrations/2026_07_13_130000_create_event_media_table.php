<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anı akışı: misafirlerin (ve ev sahibinin) etkinlik foto/videoları.
        // Görseller ProcessEventImage ile EXIF'i temizlenip WebP varyantlara
        // dönüşür; videolar dönüştürülmeden saklanır (1 vCPU — transcoding yok).
        Schema::create('event_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_guest_id')->nullable()->constrained()->cascadeOnDelete(); // null = ev sahibi yüklemesi
            $table->string('type', 10);                    // image | video
            $table->string('status', 15)->default('yayinda'); // yayinda | beklemede (onay modu)
            $table->string('path')->nullable();            // video dosyası (image'da null)
            $table->string('path_thumb')->nullable();      // image varyantları
            $table->string('path_medium')->nullable();
            $table->string('path_large')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0); // kota muhasebesi
            $table->timestamps();

            $table->index(['event_id', 'status', 'created_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('allow_uploads')->default(true)->after('is_active');       // anı akışı açık mı
            $table->boolean('require_approval')->default(false)->after('allow_uploads'); // yüklemeler önce ev sahibi onayına düşsün
        });

        Schema::table('event_guests', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('token'); // engellenen misafir yükleyemez
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_media');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['allow_uploads', 'require_approval']);
        });

        Schema::table('event_guests', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }
};
