<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            // Yeni varyant path'leri
            $table->string('path_thumb')->nullable()->after('path');
            $table->string('path_medium')->nullable()->after('path_thumb');
            $table->string('path_large')->nullable()->after('path_medium');
            $table->unsignedSmallInteger('width')->nullable()->after('path_large');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
            $table->unsignedInteger('size_bytes')->nullable()->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn(['path_thumb', 'path_medium', 'path_large', 'width', 'height', 'size_bytes']);
        });
    }
};
