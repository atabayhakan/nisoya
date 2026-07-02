<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            // Reverse geocoded konum (GPS'ten türetilmiş)
            $table->string('reverse_country_code', 2)->nullable()->after('gps_lng');
            $table->string('reverse_country_name')->nullable()->after('reverse_country_code');
            $table->string('reverse_city')->nullable()->after('reverse_country_name');
            $table->string('reverse_state')->nullable()->after('reverse_city');
            $table->timestamp('reverse_geocoded_at')->nullable()->after('reverse_state');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['reverse_country_code', 'reverse_country_name', 'reverse_city', 'reverse_state', 'reverse_geocoded_at']);
        });
    }
};