<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'category')) {
                // Şikayetin NİTELİĞİ (dolandırıcılık vs. genel). reportable_type
                // hedefin türünü söyler; category ise aciliyeti/işi belirler.
                $table->string('category')->nullable()->after('reason')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
