<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outreach_targets', function (Blueprint $table) {
            $table->id();

            // İşletme kimliği
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('sector')->nullable();     // meslek anahtarı (berber/lokanta...)
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();

            // Kaynak + tekilleştirme
            $table->string('source')->default('fixture'); // fixture | google_places
            $table->string('external_id');                // place_id ya da md5(ad|şehir)

            // Türk tespiti (bkz. App\Services\Growth\TurkishBusinessDetector)
            $table->string('detection_band')->default('ambiguous'); // turkish|not_turkish|ambiguous
            $table->unsignedTinyInteger('detection_confidence')->default(0); // 0-100
            $table->string('detection_method')->nullable();  // deterministic|llm
            $table->json('detection_signals')->nullable();
            $table->text('detection_reasoning')->nullable();
            $table->boolean('needs_review')->default(false);

            // Gönderim kapısı (bkz. App\Support\Growth\RegionPolicy)
            $table->string('marketing_status')->default('region_blocked'); // allowed|region_blocked

            // Hattaki durum: kesif → onayli → reddedildi (sonraki fazda gönderim)
            $table->string('status')->default('kesif');

            $table->timestamps();

            // Aynı işletme aynı kaynaktan tek kez.
            $table->unique(['source', 'external_id']);
            $table->index(['detection_band', 'marketing_status']);
            $table->index('country');
            $table->index('needs_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_targets');
    }
};
