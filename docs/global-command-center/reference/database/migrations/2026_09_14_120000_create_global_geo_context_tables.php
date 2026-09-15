<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_regions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_tr');
            $table->string('kind', 32); // continent, un_m49, operational
            $table->string('source_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('geo_region_country', function (Blueprint $table): void {
            $table->foreignId('geo_region_id')->constrained('geo_regions')->cascadeOnDelete();
            $table->char('country_code', 2);
            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnDelete();
            $table->primary(['geo_region_id', 'country_code']);
            $table->index('country_code');
        });
        Schema::create('geo_languages', function (Blueprint $table): void {
            $table->string('tag', 35)->primary(); // BCP 47 locale, not a country allowlist
            $table->string('name_tr');
        });
        Schema::create('geo_country_language', function (Blueprint $table): void {
            $table->char('country_code', 2);
            $table->string('language_tag', 35);
            $table->boolean('is_default')->default(false);
            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnDelete();
            $table->foreign('language_tag')->references('tag')->on('geo_languages')->cascadeOnDelete();
            $table->primary(['country_code', 'language_tag']);
        });
        Schema::create('global_growth_tasks', function (Blueprint $table): void {
            $table->id();
            $table->char('country_code', 2);
            $table->foreign('country_code')->references('code')->on('countries');
            $table->string('action_key', 40);
            $table->date('period_start');
            $table->string('status', 20)->default('suggested');
            $table->text('recommendation');
            $table->string('metric_version', 40);
            $table->timestamps();
            $table->unique(['country_code', 'action_key', 'period_start'], 'gcc_growth_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_growth_tasks');
        Schema::dropIfExists('geo_country_language');
        Schema::dropIfExists('geo_languages');
        Schema::dropIfExists('geo_region_country');
        Schema::dropIfExists('geo_regions');
    }
};
