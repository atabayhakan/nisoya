<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz M4: zengin mesajlaşma. Metin dışı mesaj türleri (fotoğraf, konum).
 * type: text (varsayılan) | image | location.
 * attachment_path: image türünde işlenmiş (EXIF strip'li) webp yolu.
 * Konum, body içinde "lat,lng" olarak saklanır (ayrı kolon gerekmez).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type', 20)->default('text')->after('sender_id');
            $table->string('attachment_path')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'attachment_path']);
        });
    }
};
