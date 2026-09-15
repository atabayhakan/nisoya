<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_assessments', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 20);
            $table->unsignedBigInteger('source_id');
            $table->char('source_hash', 64);
            $table->string('version', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('requested_by_system')->default(false);
            $table->char('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('title');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->json('findings')->nullable();
            $table->json('ai_result')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
            $table->unique(['kind', 'source_id', 'source_hash', 'version'], 'gcc_assessment_unique');
            $table->index(['country_code', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_assessments');
    }
};
