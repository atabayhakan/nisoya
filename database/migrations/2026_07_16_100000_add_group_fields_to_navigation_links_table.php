<?php

use App\Models\NavigationLink;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz H1 — üst menüyü büyüyen dikeyler (emlak, vasıta, davetiye) için
 * bir "Keşfet" mega menüsünde gruplayabilmek üzere navigation_links'e
 * nullable taksonomi alanları ekler. group_key dolu olan satırlar mega
 * menüde kart olarak, boş olanlar ikincil düz link olarak gösterilir.
 *
 * Canlıda zaten var olan 6 satırın (İlanlar, Emlak, Vasıta, İş İlanları,
 * Harita, Nasıl Çalışır?) group_key/icon/description'ı burada backfill
 * edilir — NavigationLinkSeeder firstOrCreate kullandığı için mevcut
 * satırları güncellemez, sadece yeni satır oluşturur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('navigation_links', function (Blueprint $table) {
            $table->string('group_key')->nullable()->after('url');
            $table->string('icon')->nullable()->after('group_key');
            $table->string('description')->nullable()->after('icon');
        });

        $backfill = [
            'İlanlar' => ['group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'shopping-bag', 'description' => 'Hizmet ve ev ürünü ilanları', 'sort_order' => 1],
            'Yetenek Havuzu' => ['group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'user-group', 'description' => 'Yeteneğini göster, iş bul', 'sort_order' => 2],
            'İş İlanları' => ['group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'briefcase', 'description' => 'Kariyer fırsatları', 'sort_order' => 3],
            'Emlak' => ['group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'home', 'description' => 'Satılık ve kiralık ilanlar', 'sort_order' => 4],
            'Vasıta' => ['group_key' => NavigationLink::GROUP_KESFET, 'icon' => 'truck', 'description' => 'Satılık ve kiralık araç', 'sort_order' => 5],
            'Harita' => ['icon' => 'map', 'sort_order' => 7],
            'Nasıl Çalışır?' => ['icon' => 'question-mark-circle', 'sort_order' => 8],
        ];

        foreach ($backfill as $label => $attributes) {
            NavigationLink::query()->where('label', $label)->update($attributes);
        }
    }

    public function down(): void
    {
        Schema::table('navigation_links', function (Blueprint $table) {
            $table->dropColumn(['group_key', 'icon', 'description']);
        });
    }
};
