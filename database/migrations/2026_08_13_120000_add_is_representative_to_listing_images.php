<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temsilî görsel işareti.
 *
 * NEDEN AYRI SÜTUN: Bu görselin AI ile üretildiği bilgisi görselin KENDİSİNE
 * ait ve kalıcı olmak zorunda. Kartta, detayda, paylaşım kartında —
 * göründüğü her yerde "Temsilî" yazması buna bağlı. Sonradan "hangi
 * görseller üretilmişti" diye sormak (ör. sahip bu özelliği kapatmaya karar
 * verirse toplu kaldırmak) da yalnızca sütun varsa mümkün; dosya adından ya
 * da tarihten çıkarılamaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->boolean('is_representative')->default(false)->after('is_cover');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn('is_representative');
        });
    }
};
