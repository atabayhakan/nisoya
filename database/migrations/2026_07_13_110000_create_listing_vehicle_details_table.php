<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('brand', 60)->nullable();
            $table->string('model', 60)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('mileage_km')->nullable();
            $table->string('fuel', 20)->nullable();          // benzin/dizel/hibrit/elektrik/lpg
            $table->string('transmission', 20)->nullable();  // manuel/otomatik/yari_otomatik
            $table->string('body_type', 30)->nullable();     // sedan/hatchback/suv/...
            $table->string('color', 30)->nullable();
            $table->unsignedSmallInteger('min_rental_days')->nullable(); // kiralık
            $table->decimal('deposit', 10, 2)->nullable();               // kiralık, ilan para biriminde
            $table->unsignedSmallInteger('km_limit_per_day')->nullable(); // kiralık, 0/null = sınırsız
            $table->json('badges')->nullable();              // kesin dönüş, havalimanı teslimi...
            $table->timestamps();

            $table->index('brand');
            $table->index('year');
            $table->index('mileage_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_vehicle_details');
    }
};
