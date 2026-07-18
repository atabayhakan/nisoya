<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performans indeksleri (harici inceleme #M4):
 *  - listings + job_listings (latitude, longitude): harita/yakınlık sorguları
 *    whereNotNull(latitude)->whereNotNull(longitude) ile TAM TARAMA yapıyordu
 *    (bkz. MapController).
 *  - job_listings.slug: route-model-binding slug ile arar; listings.slug'da
 *    indeks vardı, job_listings'te yoktu.
 *
 * Tümü idempotent (index varsa atla) — non-destructive, prod'da güvenli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasIndex('listings', 'idx_listings_coords')) {
                $table->index(['latitude', 'longitude'], 'idx_listings_coords');
            }
        });

        Schema::table('job_listings', function (Blueprint $table) {
            if (! Schema::hasIndex('job_listings', 'idx_job_listings_coords')) {
                $table->index(['latitude', 'longitude'], 'idx_job_listings_coords');
            }
            if (! Schema::hasIndex('job_listings', 'idx_job_listings_slug')) {
                $table->index('slug', 'idx_job_listings_slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasIndex('listings', 'idx_listings_coords')) {
                $table->dropIndex('idx_listings_coords');
            }
        });

        Schema::table('job_listings', function (Blueprint $table) {
            if (Schema::hasIndex('job_listings', 'idx_job_listings_coords')) {
                $table->dropIndex('idx_job_listings_coords');
            }
            if (Schema::hasIndex('job_listings', 'idx_job_listings_slug')) {
                $table->dropIndex('idx_job_listings_slug');
            }
        });
    }
};
