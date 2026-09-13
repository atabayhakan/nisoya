<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Takip edilecek ve içerikleri otomatik/yarı otomatik taranacak
     * yurtdışı Türk topluluğu / diaspora Instagram hesapları.
     */
    public function up(): void
    {
        Schema::create('diaspora_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('title', 150)->nullable();
            $table->text('description')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('profile_pic_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false); // Doğrulanmış topluluk hesabı
            $table->boolean('autopilot')->default(false);   // Güvenli içerikleri onaysız doğrudan yayına al
            $table->unsignedInteger('reels_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('country_code')
                ->references('code')
                ->on('countries')
                ->nullOnDelete();

            $table->index(['country_code', 'is_active']);
            $table->index(['is_active', 'autopilot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diaspora_accounts');
    }
};
