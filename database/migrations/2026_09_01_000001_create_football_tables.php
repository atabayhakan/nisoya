<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->char('country_code', 2);
            $table->string('logo_path')->nullable();
            $table->string('primary_kit_color')->nullable();
            $table->string('secondary_kit_color')->nullable();
            $table->string('level')->default('orta');
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('matches_count')->default(0);
            $table->unsignedInteger('wins_count')->default(0);
            $table->unsignedInteger('draws_count')->default(0);
            $table->unsignedInteger('losses_count')->default(0);
            $table->unsignedInteger('goals_for')->default(0);
            $table->unsignedInteger('goals_against')->default(0);
            $table->integer('points')->default(0);
            $table->timestamps();

            $table->index(['country_code', 'city']);
            $table->index(['city', 'points']);
        });

        Schema::create('football_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('football_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('player');
            $table->string('status')->default('aktif');
            $table->unsignedSmallInteger('jersey_number')->nullable();
            $table->string('primary_position')->nullable();
            $table->dateTime('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('football_player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->json('positions')->nullable();
            $table->string('preferred_foot')->nullable();
            $table->string('level')->default('orta');
            $table->text('bio')->nullable();
            $table->boolean('is_looking_for_team')->default(false);
            $table->boolean('is_looking_for_match')->default(false);
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('goals')->default(0);
            $table->unsignedInteger('assists')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->unsignedInteger('ratings_count')->default(0);
            $table->timestamps();

            $table->index(['country_code', 'city']);
            $table->index(['is_looking_for_team', 'city']);
            $table->index(['is_looking_for_match', 'city']);
        });

        Schema::create('football_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->char('country_code', 2);
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('pitch_type')->default('kapali');
            $table->string('surface_type')->default('suni_cim');
            $table->json('features')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('price_info')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['country_code', 'city']);
        });

        Schema::create('football_venue_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('football_venues')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->json('sub_ratings')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('yayinda');
            $table->timestamps();

            $table->index(['venue_id', 'status']);
        });

        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_team_id')->constrained('football_teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('football_teams')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('football_venues')->nullOnDelete();
            $table->string('venue_custom_name')->nullable();
            $table->string('city');
            $table->char('country_code', 2);
            $table->dateTime('match_date');
            $table->string('status')->default('planlandi');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->string('result_status')->default('beklemede');
            $table->foreignId('result_submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('result_verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('dispute_reason')->nullable();
            $table->foreignId('mvp_player_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('home_scorers')->nullable();
            $table->json('away_scorers')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('news_title')->nullable();
            $table->text('news_summary')->nullable();
            $table->text('news_body')->nullable();
            $table->dateTime('news_generated_at')->nullable();
            $table->timestamps();

            $table->index(['city', 'match_date']);
            $table->index(['result_status', 'city']);
            $table->index('is_featured');
        });

        Schema::create('football_player_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('football_teams')->nullOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('football_matches')->nullOnDelete();
            $table->string('type')->default('oyuncu_araniyor');
            $table->string('city');
            $table->char('country_code', 2);
            $table->dateTime('match_time')->nullable();
            $table->string('venue_name')->nullable();
            $table->unsignedSmallInteger('needed_count')->default(1);
            $table->string('level')->nullable();
            $table->json('positions')->nullable();
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['city', 'is_active']);
            $table->index(['type', 'is_active']);
        });

        Schema::create('football_player_request_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('football_player_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('beklemede');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(['request_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_player_request_applications');
        Schema::dropIfExists('football_player_requests');
        Schema::dropIfExists('football_matches');
        Schema::dropIfExists('football_venue_reviews');
        Schema::dropIfExists('football_venues');
        Schema::dropIfExists('football_player_profiles');
        Schema::dropIfExists('football_team_members');
        Schema::dropIfExists('football_teams');
    }
};
