<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ana sayfada sergilenecek küratörlü Diaspora Instagram Reels ve paylaşımları.
     * Türk diasporasının yoğun olduğu ülkelerden (DE, KG, NL vb.) kültürel,
     * ticari ve topluluk anları/hikayeleri.
     */
    public function up(): void
    {
        Schema::create('diaspora_reels', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('caption')->nullable();
            $table->string('instagram_url', 500);
            $table->string('shortcode', 50)->nullable()->index();
            $table->string('instagram_username', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('country_code')
                ->references('code')
                ->on('countries')
                ->nullOnDelete();

            $table->index(['is_active', 'sort_order']);
            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diaspora_reels');
    }
};
