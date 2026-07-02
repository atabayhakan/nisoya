<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Google/Facebook OAuth kaldırıldı: provider/provider_id kolonları düşürülüyor.
        // Kolon mevcut değilse (henüz sosyal login eklenmemiş temiz kurulum) sessizce geç.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'provider')) {
                $table->dropColumn('provider');
            }
            if (Schema::hasColumn('users', 'provider_id')) {
                $table->dropColumn('provider_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
        });
    }
};