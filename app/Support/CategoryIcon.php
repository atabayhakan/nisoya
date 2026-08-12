<?php

namespace App\Support;

/**
 * Kategori emoji ikonlarını (CategorySeeder/ProductCategorySeeder'da tutulan
 * `categories.icon` kolonu) görsel bağlamlarda Heroicon SVG'lerine eşler.
 * `<select><option>` / `<optgroup label>` gibi native form elemanları SVG
 * render edemediği için `icon` kolonunun kendisi emoji olarak kalıyor —
 * bu yalnızca kart/ızgara gibi gerçek HTML render edilen yerler için bir
 * sunum katmanı eşlemesi.
 */
class CategoryIcon
{
    private const MAP = [
        '🚨' => 'lifebuoy',
        '➕' => 'plus-circle',
        '🩺' => 'plus-circle',
        '⚖️' => 'scale',
        '🕊️' => 'heart',
        '🔑' => 'key',
        '📚' => 'academic-cap',
        '🔧' => 'wrench-screwdriver',
        '🍽️' => 'cake',
        '💇' => 'scissors',
        '👶' => 'face-smile',
        '📷' => 'camera',
        '💻' => 'computer-desktop',
        '📝' => 'pencil-square',
        '🚗' => 'truck',
        '🧰' => 'wrench',
        '🍯' => 'cake',
        '🧶' => 'scissors',
        '💍' => 'sparkles',
        '🏠' => 'home',
        '🎨' => 'paint-brush',
        '🧼' => 'beaker',
        '🧸' => 'gift',
        '📦' => 'archive-box',
    ];

    public static function heroicon(?string $emoji): string
    {
        return self::MAP[$emoji] ?? 'squares-2x2';
    }
}
