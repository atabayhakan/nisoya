<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // 12 aylık medya saklama politikası: silmeden 1 ay önce ev sahibi
            // e-postayla uyarılır; bu damga uyarının tekrar gönderilmesini önler.
            $table->timestamp('media_purge_warned_at')->nullable()->after('require_approval');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('media_purge_warned_at');
        });
    }
};
