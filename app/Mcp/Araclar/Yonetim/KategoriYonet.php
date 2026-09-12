<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_kategori_yonet')]
#[Title('Kategori Yönetimi — Pazaryeri kategorilerini listele, ara veya oluştur/güncelle')]
#[Description(
    'Nisoya pazaryeri kategorilerini yönetir. '.
    'islem="listele" (tüm kategori ağacını listeler/arar), '.
    'islem="ekle" (yeni kategori oluşturur), '.
    'islem="guncelle" (kategori adı, emoji veya durumunu günceller).'
)]
class KategoriYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "ekle", "guncelle".')
                ->required(),
            'id' => $schema->integer()
                ->description('Güncellenecek kategori ID numarası.'),
            'name' => $schema->string()
                ->description('Kategori adı.'),
            'slug' => $schema->string()
                ->description('Kısa ad (URL). Belirtilmezse isimden türetilir.'),
            'parent_id' => $schema->integer()
                ->description('Üst kategori ID numarası (ana kategori ise boş bırakılır).'),
            'icon' => $schema->string()
                ->description('Kategori emojisi/ikonu (ör: 🚗, 🏠, 📱).'),
            'type' => $schema->string()
                ->description('Kategori türü: "hizmet" veya "urun".'),
            'is_active' => $schema->boolean()
                ->description('Kategorinin yayında olup olmadığı.'),
            'sort_order' => $schema->integer()
                ->description('Görüntülenme sıra numarası.'),
            'arama' => $schema->string()
                ->description('Listeleme sırasında kategori adı içinde aranacak kelime.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'ekle' => $this->ekle($request),
            'guncelle' => $this->guncelle($request),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $query = Category::query()->withCount('listings');

        if ($request->has('arama') && filled($request->get('arama'))) {
            $arama = (string) $request->get('arama');
            $query->where('name', 'like', "%{$arama}%");
        }

        $kategoriler = $query->orderBy('sort_order')->get();

        return [
            'toplam_kategori' => $kategoriler->count(),
            'kategoriler' => $kategoriler->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => $c->icon ?: '🏷️',
                'parent_id' => $c->parent_id,
                'type' => $c->type->value,
                'is_active' => (bool) $c->is_active,
                'sort_order' => $c->sort_order,
                'ilan_sayisi' => $c->listings_count,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function ekle(Request $request): array
    {
        $name = trim((string) $request->get('name', ''));
        if ($name === '') {
            return [
                'basarili' => false,
                'mesaj' => 'Yeni kategori eklemek için "name" zorunludur.',
            ];
        }

        $slug = trim((string) $request->get('slug', ''));
        if ($slug === '') {
            $slug = Str::slug($name);
        }

        if (Category::where('slug', $slug)->exists()) {
            return [
                'basarili' => false,
                'mesaj' => "'{$slug}' kısa adı (slug) zaten mevcut.",
            ];
        }

        $typeStr = (string) $request->get('type', 'hizmet');
        $type = match ($typeStr) {
            'urun' => CategoryType::Urun,
            default => CategoryType::Hizmet,
        };

        $cat = Category::create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $request->has('parent_id') ? (int) $request->get('parent_id') : null,
            'icon' => $request->get('icon') ?: '🏷️',
            'type' => $type,
            'sort_order' => (int) $request->get('sort_order', 0),
            'is_active' => $request->has('is_active') ? (bool) $request->get('is_active') : true,
        ]);

        return [
            'basarili' => true,
            'mesaj' => "Kategori '{$cat->name}' başarıyla oluşturuldu.",
            'id' => $cat->id,
            'slug' => $cat->slug,
        ];
    }

    /** @return array<string, mixed> */
    private function guncelle(Request $request): array
    {
        $id = (int) $request->get('id');
        $cat = Category::find($id);

        if (! $cat) {
            return [
                'basarili' => false,
                'mesaj' => "ID'si {$id} olan kategori bulunamadı.",
            ];
        }

        if ($request->has('name')) {
            $cat->name = trim((string) $request->get('name'));
        }
        if ($request->has('icon')) {
            $cat->icon = trim((string) $request->get('icon'));
        }
        if ($request->has('is_active')) {
            $cat->is_active = (bool) $request->get('is_active');
        }
        if ($request->has('sort_order')) {
            $cat->sort_order = (int) $request->get('sort_order');
        }
        if ($request->has('parent_id')) {
            $cat->parent_id = $request->get('parent_id') ? (int) $request->get('parent_id') : null;
        }

        $cat->save();

        return [
            'basarili' => true,
            'mesaj' => "Kategori '{$cat->name}' (#{$id}) başarıyla güncellendi.",
            'kategori' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'icon' => $cat->icon,
                'is_active' => (bool) $cat->is_active,
            ],
        ];
    }
}
