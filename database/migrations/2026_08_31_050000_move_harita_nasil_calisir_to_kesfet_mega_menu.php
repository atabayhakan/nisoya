<?php

use App\Models\NavigationLink;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        NavigationLink::query()->where('label', 'Harita')->update([
            'group_key' => NavigationLink::GROUP_KESFET,
            'icon' => 'map',
            'description' => 'İlanları haritada keşfet',
            'sort_order' => 7,
        ]);

        NavigationLink::query()->where('label', 'Nasıl Çalışır?')->update([
            'group_key' => NavigationLink::GROUP_KESFET,
            'icon' => 'question-mark-circle',
            'description' => 'Nisoya kullanım rehberi',
            'sort_order' => 8,
        ]);

        Cache::forget(NavigationLink::CACHE_KEY);
    }

    public function down(): void
    {
        NavigationLink::query()->whereIn('label', ['Harita', 'Nasıl Çalışır?'])->update([
            'group_key' => null,
        ]);

        Cache::forget(NavigationLink::CACHE_KEY);
    }
};
