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
        Schema::table('listing_images', function (Blueprint $table) {
            $table->unsignedTinyInteger('focal_x')->default(50)->after('sort_order');
            $table->unsignedTinyInteger('focal_y')->default(50)->after('focal_x');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['focal_x', 'focal_y']);
        });
    }
};
