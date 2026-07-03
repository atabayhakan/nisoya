<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payment_links tablosuna taşındı (bkz. create_payment_links_table
        // migration'ındaki veri taşıma adımı) — artık kullanılmıyor.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('preferred_currency');
        });
    }
};
