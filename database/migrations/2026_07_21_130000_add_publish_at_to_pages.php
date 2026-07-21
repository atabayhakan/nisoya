<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zamanlanmış (ileri tarihli) yayın (Faz 4). publish_at doluysa ve gelecekteyse,
 * sayfa "Yayında" olsa bile o tarihe kadar ziyaretçilere görünmez
 * (bkz. Page::scopePublished). Boş = hemen yayında.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->timestamp('publish_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('publish_at');
        });
    }
};
