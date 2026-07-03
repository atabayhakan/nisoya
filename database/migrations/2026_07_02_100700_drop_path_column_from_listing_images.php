<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `path` kolonu artık kullanılmıyor — `path_large` aynı değeri tutuyor
        // (yeni yüklenen görsellerde path_large = path). DB'den kaldır.
        // DİKKAT: Eski tek-path kayıtları (path_thumb/medium/large null) için
        // geriye uyumluluk kaybedilebilir. Eğer mevcut DB'de path_thumb=null
        // ve path='listings/legacy.jpg' gibi kayıtlar varsa, bunlar path_large=null
        // olur ve görsel bozuk görünür. Bu durum için reprocess_images komutu
        // çalıştırılabilir.
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->string('path')->nullable()->after('listing_id');
        });
    }
};
