<?php

declare(strict_types=1);

namespace App\Mcp\Araclar\Yonetim;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;

#[Name('nisoya_sayfa_yonet')]
#[Title('CMS Sayfa Yönetimi — Kurumsal ve statik sayfaları listele, incele veya yönet')]
#[Description(
    'Nisoya CMS sayfalarını (Hakkımızda, Gizlilik Sözleşmesi, Kullanım Koşulları vb.) yönetir. '.
    'islem: "listele" (tüm sayfaları durumlarıyla listeler), "detay" (sayfa içeriğini getirir), '.
    '"olustur" (yeni sayfa ekler), "guncelle" (başlık, SEO veya durumunu günceller).'
)]
class SayfaYonet extends YonetimAraci
{
    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'islem' => $schema->string()
                ->description('İşlem türü: "listele", "detay", "olustur", "guncelle".')
                ->required(),
            'id' => $schema->integer()
                ->description('Sayfa ID numarası (detay veya güncelleme için).'),
            'slug' => $schema->string()
                ->description('Sayfa kısa adı / URL adresi (detay, oluşturma veya güncelleme için).'),
            'title' => $schema->string()
                ->description('Sayfa başlığı.'),
            'status' => $schema->string()
                ->description('Sayfa durumu: "taslak", "yayinda", "arsivde".'),
            'meta_description' => $schema->string()
                ->description('SEO meta açıklaması.'),
            'show_in_footer' => $schema->boolean()
                ->description('Footer menüsünde gösterilip gösterilmeyeceği.'),
            'sort_order' => $schema->integer()
                ->description('Sıralama değeri.'),
        ];
    }

    /** @return array<string, mixed> */
    protected function calistir(Request $request): array
    {
        $islem = strtolower((string) $request->get('islem', 'listele'));

        return match ($islem) {
            'detay' => $this->detay($request),
            'olustur' => $this->olustur($request),
            'guncelle' => $this->guncelle($request),
            default => $this->listele($request),
        };
    }

    /** @return array<string, mixed> */
    private function listele(Request $request): array
    {
        $sayfalar = Page::query()->orderBy('sort_order')->get();

        return [
            'toplam_sayfa' => $sayfalar->count(),
            'sayfalar' => $sayfalar->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'url' => url('/'.$p->slug),
                'status' => $p->status->value,
                'status_etiket' => $p->status->getLabel(),
                'show_in_footer' => (bool) $p->show_in_footer,
                'meta_description' => $p->meta_description,
                'sort_order' => $p->sort_order,
                'blok_sayisi' => is_array($p->blocks) ? count($p->blocks) : 0,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function detay(Request $request): array
    {
        $id = $request->get('id');
        $slug = $request->get('slug');

        $query = Page::query();
        if ($id) {
            $query->where('id', (int) $id);
        } elseif ($slug) {
            $query->where('slug', (string) $slug);
        } else {
            return [
                'basarili' => false,
                'mesaj' => 'Detay için "id" veya "slug" parametresi belirtilmelidir.',
            ];
        }

        $sayfa = $query->first();
        if (! $sayfa) {
            return [
                'basarili' => false,
                'mesaj' => 'İstenen sayfa bulunamadı.',
            ];
        }

        return [
            'basarili' => true,
            'sayfa' => [
                'id' => $sayfa->id,
                'title' => $sayfa->title,
                'slug' => $sayfa->slug,
                'url' => url('/'.$sayfa->slug),
                'status' => $sayfa->status->value,
                'status_etiket' => $sayfa->status->getLabel(),
                'meta_description' => $sayfa->meta_description,
                'show_in_footer' => (bool) $sayfa->show_in_footer,
                'sort_order' => $sayfa->sort_order,
                'publish_at' => $sayfa->publish_at?->toIso8601String(),
                'blocks' => $sayfa->blocks ?? [],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function olustur(Request $request): array
    {
        $title = trim((string) $request->get('title', ''));
        if ($title === '') {
            return [
                'basarili' => false,
                'mesaj' => 'Yeni sayfa oluşturmak için "title" zorunludur.',
            ];
        }

        $slug = trim((string) $request->get('slug', ''));
        if ($slug === '') {
            $slug = Str::slug($title);
        }

        if (Page::where('slug', $slug)->exists()) {
            return [
                'basarili' => false,
                'mesaj' => "'{$slug}' kısa adı (slug) zaten kullanımda.",
            ];
        }

        $statusStr = strtolower((string) $request->get('status', 'taslak'));
        $status = match ($statusStr) {
            'yayinda', 'yayin' => PageStatus::Yayin,
            default => PageStatus::Taslak,
        };

        $sayfa = Page::create([
            'title' => $title,
            'slug' => $slug,
            'status' => $status,
            'meta_description' => $request->get('meta_description') ?: null,
            'show_in_footer' => (bool) $request->get('show_in_footer', false),
            'sort_order' => (int) $request->get('sort_order', 0),
            'blocks' => [],
        ]);

        return [
            'basarili' => true,
            'mesaj' => "Sayfa '{$sayfa->title}' başarıyla oluşturuldu.",
            'id' => $sayfa->id,
            'slug' => $sayfa->slug,
            'url' => url('/'.$sayfa->slug),
        ];
    }

    /** @return array<string, mixed> */
    private function guncelle(Request $request): array
    {
        $id = $request->get('id');
        $slug = $request->get('slug');

        $query = Page::query();
        if ($id) {
            $query->where('id', (int) $id);
        } elseif ($slug) {
            $query->where('slug', (string) $slug);
        } else {
            return [
                'basarili' => false,
                'mesaj' => 'Güncelleme için "id" veya "slug" parametresi belirtilmelidir.',
            ];
        }

        $sayfa = $query->first();
        if (! $sayfa) {
            return [
                'basarili' => false,
                'mesaj' => 'Güncellenecek sayfa bulunamadı.',
            ];
        }

        if ($request->has('title')) {
            $sayfa->title = trim((string) $request->get('title'));
        }
        if ($request->has('meta_description')) {
            $sayfa->meta_description = trim((string) $request->get('meta_description'));
        }
        if ($request->has('show_in_footer')) {
            $sayfa->show_in_footer = (bool) $request->get('show_in_footer');
        }
        if ($request->has('sort_order')) {
            $sayfa->sort_order = (int) $request->get('sort_order');
        }
        if ($request->has('status')) {
            $statusStr = strtolower((string) $request->get('status'));
            $status = match ($statusStr) {
                'yayinda', 'yayin' => PageStatus::Yayin,
                'taslak' => PageStatus::Taslak,
                default => null,
            };
            if ($status !== null) {
                $sayfa->status = $status;
            }
        }

        $sayfa->save();

        return [
            'basarili' => true,
            'mesaj' => "Sayfa '{$sayfa->title}' başarıyla güncellendi.",
            'sayfa' => [
                'id' => $sayfa->id,
                'title' => $sayfa->title,
                'slug' => $sayfa->slug,
                'status' => $sayfa->status->value,
                'status_etiket' => $sayfa->status->getLabel(),
            ],
        ];
    }
}
