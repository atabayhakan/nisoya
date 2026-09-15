<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('diaspora_reels')->whereNotNull('shortcode')->groupBy('shortcode')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Tekrarlanan shortcode kayıtlarını inceleyip birleştirmeden unique index eklenemez.');
        }
        if (DB::getDriverName() === 'mysql') {
            Schema::table('diaspora_reels', function (Blueprint $table): void {
                $table->string('shortcode', 50)->nullable()->collation('utf8mb4_bin')->change();
            });
        }
        Schema::table('diaspora_reels', function (Blueprint $table): void {
            $table->unique('shortcode', 'gcc_reels_shortcode_unique');
            $table->unsignedInteger('views_count')->nullable()->default(null)->change();
            $table->unsignedInteger('likes_count')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('diaspora_reels', fn (Blueprint $table) => $table->dropUnique('gcc_reels_shortcode_unique'));
        // Preserve unknown measurements; converting NULL to zero would corrupt their meaning.
    }
};
