<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diaspora Reels tablosuna akıllı moderasyon, durum, kategori ve
     * etkileşim skoru sütunlarını ekler.
     */
    public function up(): void
    {
        Schema::table('diaspora_reels', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('id')
                ->constrained('diaspora_accounts')
                ->nullOnDelete();

            $table->string('status', 20)
                ->default('published')
                ->after('is_active')
                ->index(); // published, draft (onay bekleyen), archived

            $table->string('category', 50)
                ->nullable()
                ->default('genel')
                ->after('city')
                ->index(); // gastronomi, etkinlik, spor, rehber, topluluk, genel

            $table->unsignedTinyInteger('safety_score')
                ->nullable()
                ->after('category'); // 0-100 AI güvenilirlik/uygunluk skoru

            $table->string('safety_status', 20)
                ->default('safe')
                ->after('safety_score'); // safe, review_needed, rejected

            $table->unsignedInteger('engagement_score')
                ->default(0)
                ->after('safety_status')
                ->index(); // Akıllı popülerlik sıralama puanı

            $table->unsignedInteger('views_count')
                ->default(0)
                ->after('engagement_score');

            $table->unsignedInteger('likes_count')
                ->default(0)
                ->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('diaspora_reels', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn([
                'account_id',
                'status',
                'category',
                'safety_score',
                'safety_status',
                'engagement_score',
                'views_count',
                'likes_count',
            ]);
        });
    }
};
