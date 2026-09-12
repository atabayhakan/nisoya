<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->boolean('is_claimed')->default(true)->after('is_demo');
            $table->string('claim_token', 64)->nullable()->unique()->after('is_claimed');
            $table->string('claim_email')->nullable()->after('claim_token');
            $table->string('claim_phone')->nullable()->after('claim_email');
            $table->timestamp('claimed_at')->nullable()->after('claim_phone');
            $table->string('source_external_id')->nullable()->index()->after('claimed_at');
        });

        if (Schema::hasTable('outreach_targets')) {
            Schema::table('outreach_targets', function (Blueprint $table) {
                $table->foreignId('listing_id')->nullable()->after('status')->constrained('listings')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('outreach_targets')) {
            Schema::table('outreach_targets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('listing_id');
            });
        }

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['source_external_id']);
            $table->dropColumn([
                'is_claimed',
                'claim_token',
                'claim_email',
                'claim_phone',
                'claimed_at',
                'source_external_id',
            ]);
        });
    }
};
