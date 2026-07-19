<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'deal_id')) {
                // Tamamlanmış bir anlaşmaya dayanan değerlendirme "Doğrulanmış
                // işlem" rozeti alır (K-C). Konuşma-bağlı (deal_id null)
                // değerlendirmeler de geçerlidir, sadece rozetsizdir.
                $table->foreignId('deal_id')->nullable()->after('listing_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'deal_id')) {
                $table->dropConstrainedForeignId('deal_id');
            }
        });
    }
};
