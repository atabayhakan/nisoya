<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_growth_tasks', function (Blueprint $table): void {
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->index(['country_code', 'status', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('global_growth_tasks', function (Blueprint $table): void {
            $table->dropIndex(['country_code', 'status', 'assignee_id']);
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn(['completed_at', 'completion_note', 'revision']);
        });
    }
};
