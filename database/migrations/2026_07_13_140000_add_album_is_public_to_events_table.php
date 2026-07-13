<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Ev sahibi albümü herkese açarsa /mutlu-anlar vitrininde görünür
            // (varsayılan özel — KVKK; açma kararı ve sorumluluğu ev sahibinde).
            $table->boolean('album_is_public')->default(false)->after('require_approval');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('album_is_public');
        });
    }
};
