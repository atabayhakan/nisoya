<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('video_url')->nullable()->after('logo_path');
            $table->string('social_whatsapp')->nullable()->after('social_instagram');
            $table->string('social_twitter')->nullable()->after('social_whatsapp');
            $table->string('address')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['video_url', 'social_whatsapp', 'social_twitter', 'address']);
        });
    }
};
