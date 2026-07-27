<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * İletişim gelen kutusunu destek bileti sistemine genişletir (2026-07-27).
 *
 * Yeni tablo YERİNE mevcut contact_messages genişletiliyor: geçmiş mesajlar
 * olduğu yerde kalsın, public form sözleşmesi (ContactMessageController)
 * hiç değişmesin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('priority')->default('normal')->after('category');
            // Atanan kişi silinirse bilet atanmamışa döner (bilet kaybolmaz).
            $table->foreignId('assigned_to')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('first_replied_at')->nullable()->after('admin_note');
            $table->timestamp('closed_at')->nullable()->after('first_replied_at');

            // Sekme sayımları (durum + atanan) ve "acil olanlar üstte" sıralaması.
            $table->index(['status', 'priority']);
            $table->index('assigned_to');
        });

        Schema::create('contact_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            // Yanıtı gönderen yönetici silinirse yanıt metni korunur (kim
            // yazdığı kaybolur ama yazışma geçmişi bozulmaz).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            // Kuyruk gönderimi sessizce başarısız olabilir; başarısızlık
            // bilette görünür olmalı, yoksa misafir yanıt beklerken bilet
            // "yanıtlandı" görünür (keşif bulgusu).
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('contact_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_message_replies');

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority']);
            $table->dropIndex(['assigned_to']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['priority', 'first_replied_at', 'closed_at']);
        });
    }
};
