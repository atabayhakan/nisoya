<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_scan_cursors', function (Blueprint $table): void {
            $table->string('kind', 20)->primary();
            $table->unsignedBigInteger('last_id')->default(0);
            $table->unsignedBigInteger('through_id')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamp('cycle_completed_at')->nullable();
        });
        Schema::table('content_assessments', fn (Blueprint $table) => $table->unsignedInteger('attempt')->default(1));
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_scan_cursors');
        Schema::table('content_assessments', fn (Blueprint $table) => $table->dropColumn('attempt'));
    }
};
