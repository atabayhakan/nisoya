<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->char('preferred_currency', 3)->default('EUR');
            $table->string('role')->default('uye');
            $table->boolean('is_verified')->default(false);
            $table->string('status')->default('aktif');
            $table->timestamp('last_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'avatar_path', 'bio', 'country_code',
                'city', 'preferred_currency', 'role', 'is_verified', 'status', 'last_seen_at',
            ]);
        });
    }
};
