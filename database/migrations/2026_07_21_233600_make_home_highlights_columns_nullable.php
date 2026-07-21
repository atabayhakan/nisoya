<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ana sayfa vurgu mesajlarında başlık, metin ve ikon alanlarını opsiyonel yapmak
     * için kolonları nullable tanımlıyoruz.
     */
    public function up(): void
    {
        Schema::table('home_highlights', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->string('text')->nullable()->change();
            $table->string('icon')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('home_highlights', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->string('text')->nullable(false)->change();
            $table->string('icon')->nullable(false)->change();
        });
    }
};
