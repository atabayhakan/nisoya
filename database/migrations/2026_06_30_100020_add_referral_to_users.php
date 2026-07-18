<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NOT: Eskiden ->after('provider_id') vardı ama 'provider_id' kolonu
            // HİÇBİR migration'da oluşturulmuyor (yalnızca sonraki bir migration
            // onu drop etmeye çalışıyor). MySQL'de var olmayan kolona ->after
            // ERROR 1054 verir → taze kurulum patlıyordu (SQLite ->after'ı yok
            // saydığı için yerelde/testte görünmüyordu). Kolon sırası kozmetik;
            // ->after kaldırıldı.
            $table->string('referral_code', 12)->nullable()->unique();
            $table->foreignId('referred_by')->nullable()
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropColumn('referral_code');
        });
    }
};
