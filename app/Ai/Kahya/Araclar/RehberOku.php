<?php

namespace App\Ai\Kahya\Araclar;

use App\Services\Rehber\ElKitabiRehberi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * El Kitabı rehber sayfasının TAM metnini getirir.
 *
 * ---------------------------------------------------------------------------
 * NEDEN AYRI BİR ARAÇ (yönergeye basmak yerine)
 *
 * On bir sayfanın gövdesini yönergeye koymak bağlam penceresini şişirir ve
 * asıl bağlamı (panel haritası, hafıza, görev defteri, site durumu) dışarı
 * iter. Yönergede yalnız DİZİN var: slug + başlık + özet. Kâhya hangi sayfanın
 * ilgili olduğunu dizinden seçer, tam metni buradan ister.
 *
 * ---------------------------------------------------------------------------
 * ASIL İŞLEVİ: UYDURMAYI ENGELLEMEK
 *
 * Kullanıcı TEK KİŞİ. Yanlış bir cevabı yakalayacak ikinci bir kullanıcı yok.
 * Bu yüzden yönergedeki kural şu: "nasıl yaparım" sorularında ÖNCE bu aracı
 * çağır, cevabı sayfadan ALINTIYLA ver; sayfa yoksa "rehberde bu yok" de.
 *
 * Araç, olmayan bir slug istendiğinde mevcut slug listesini geri döndürür —
 * model uydurmak yerine doğrusunu seçebilsin.
 */
class RehberOku implements Tool
{
    public function __construct(private readonly ElKitabiRehberi $rehber) {}

    public function name(): string
    {
        return 'rehber-oku';
    }

    public function description(): Stringable|string
    {
        return 'El Kitabı rehber sayfasının tam metnini getirir. Sahip "nasıl yaparım", '
            .'"ne oluyor", "neden böyle" gibi bir soru sorduğunda ÖNCE bunu çağır ve '
            .'cevabını sayfadan alıntıyla ver. slug, yönergendeki rehber dizininden '
            .'birebir alınmalı. İlgili sayfa yoksa uydurma: sahibe "rehberde bu konu yok" de.';
    }

    public function handle(Request $request): Stringable|string
    {
        $slug = trim((string) ($request->all()['slug'] ?? ''));
        $sayfa = $this->rehber->bul($slug);

        if ($sayfa === null) {
            $mevcut = $this->rehber->tumSayfalar()->pluck('slug')->implode(', ');

            return "HATA: \"{$slug}\" adlı rehber sayfası yok. Mevcut sayfalar: {$mevcut}. "
                .'Bunlardan biri konuya uymuyorsa sahibe "rehberde bu konu yok" de, uydurma.';
        }

        return "REHBER SAYFASI: {$sayfa->baslik}\n\n{$sayfa->govde}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()
                ->description('Rehber dizinindeki sayfa slug\'ı, birebir (ör. yedekleme-ve-kurtarma).')
                ->required(),
        ];
    }
}
