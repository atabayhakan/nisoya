<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dikeyler arası ortak müsaitlik takvimi: ilan sahibinin "dolu"
        // işaretlediği tarih aralıkları. Emlak (kısa dönem) kullanır;
        // Faz E2'de kiralık araçlar da aynı tabloyu kullanacak.
        Schema::create('listing_unavailable_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('note')->nullable(); // ilan sahibine özel not, ziyaretçiye gösterilmez
            $table->timestamps();

            $table->index(['listing_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_unavailable_ranges');
    }
};
