<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_code_metadata', function (Blueprint $table): void {
            $table->char('country_code', 2)->primary();
            $table->foreign('country_code')->references('code')->on('countries')->cascadeOnDelete();
            $table->string('code_system', 24);
            $table->string('name_en')->nullable();
            $table->char('alpha3', 3)->nullable();
            $table->char('numeric', 3)->nullable();
            $table->json('currencies')->nullable();
            $table->string('source_version');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_code_metadata');
    }
};
