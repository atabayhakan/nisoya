<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->string('employment_type'); // EmploymentType enum
            $table->string('experience_level')->nullable(); // ExperienceLevel enum
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->string('salary_period')->nullable(); // SalaryPeriod enum
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->date('deadline')->nullable();
            $table->unsignedSmallInteger('positions')->default(1);
            $table->string('status')->default('aktif'); // JobStatus enum
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('job_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
