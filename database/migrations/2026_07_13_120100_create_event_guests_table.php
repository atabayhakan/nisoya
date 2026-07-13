<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('status', 20);                       // RsvpStatus enum
            $table->unsignedTinyInteger('party_size')->default(1); // kendisi dahil kişi sayısı
            $table->string('note')->nullable();                 // "çocuklu geliyoruz", alerji vb.
            $table->string('token', 24)->unique();              // misafirin kendi LCV'sini güncellemesi + D2'de yüklemelerin sahipliği
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_guests');
    }
};
