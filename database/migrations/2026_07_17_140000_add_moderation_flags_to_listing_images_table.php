<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI görsel moderasyonu: yüklenen ilan görseli AI tarafından uygunsuz
 * bulunursa işaretlenir (silinmez — nihai karar admin'e ait, bkz.
 * App\Services\ImageModerationService + ProcessListingImage job).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->boolean('is_flagged')->default(false)->after('is_cover');
            $table->string('flagged_reason')->nullable()->after('is_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['is_flagged', 'flagged_reason']);
        });
    }
};
