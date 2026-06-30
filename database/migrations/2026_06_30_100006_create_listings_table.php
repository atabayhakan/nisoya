<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('hizmet'); // hizmet | urun
            $table->string('title');
            $table->string('slug')->index();
            $table->text('description');
            $table->decimal('price', 12, 2)->nullable(); // null = görüşülür
            $table->char('currency', 3)->default('EUR');
            $table->string('price_unit')->default('gorusulur');
            $table->char('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_remote')->default(false);
            $table->unsignedInteger('stock')->nullable(); // ürün için (Faz 2)
            $table->string('status')->default('beklemede');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['country_code', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
