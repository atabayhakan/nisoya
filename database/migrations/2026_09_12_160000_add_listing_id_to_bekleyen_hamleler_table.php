<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bekleyen_hamleler', function (Blueprint $table) {
            $table->foreignId('listing_id')->nullable()->after('kahya_gorevi_id')
                ->constrained('listings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bekleyen_hamleler', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_id');
        });
    }
};
