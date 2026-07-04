<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Mesaj "geri alındı" damgası. Gönderen kendi mesajını geri
            // alabilir; body DB'de kalır (moderasyon için) ama arayüzde
            // "Bu mesaj geri alındı" olarak gösterilir.
            $table->timestamp('recalled_at')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('recalled_at');
        });
    }
};
