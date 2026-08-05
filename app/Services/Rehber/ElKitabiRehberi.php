<?php

namespace App\Services\Rehber;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * `docs/rehber/*.md` okuyucusu — El Kitabı'nın omurgası.
 *
 * ---------------------------------------------------------------------------
 * NEDEN MARKDOWN, NEDEN VERİTABANI DEĞİL
 *
 * Rehber metni ÜRÜNÜN KENDİSİYLE birlikte değişir: yeni bir ekran eklenince
 * aynı commit'te sayfası da yazılır, kod incelemesinde birlikte görülür ve
 * git'te sürümlenir. Veritabanında olsaydı kod ile metin ayrı zamanlarda
 * değişir, hangi sürümde ne yazdığı kaybolurdu.
 *
 * ---------------------------------------------------------------------------
 * FRONT-MATTER SÖZLEŞMESİ
 *
 *     ---
 *     baslik: Yedekleme ve kurtarma
 *     ozet: Tek satırlık özet — boş durumlarda ve aramada görünür.
 *     sira: 20
 *     ekran: App\Filament\Pages\Yedekleme      # opsiyonel
 *     etiketler: yedek, kurtarma, acil
 *     ---
 *
 * Ayrıştırıcı BİLEREK basit (symfony/yaml depoda yok ve bir doküman başlığı
 * için bağımlılık eklemeye değmez): `anahtar: değer` satırları, `etiketler`
 * virgülle ayrılır. Karmaşık YAML gerekirse o gün pakete geçilir.
 */
class ElKitabiRehberi
{
    private const CACHE_ANAHTARI = 'rehber.sayfalar';

    private const CACHE_SANIYE = 600;

    /**
     * Tüm sayfalar, `sira` sonra `baslik` ile sıralı.
     *
     * @return Collection<int, RehberSayfasi>
     */
    public function tumSayfalar(): Collection
    {
        // Yerelde önbelleksiz: markdown'ı düzenleyip sayfayı yenilediğinde
        // değişikliği ANINDA görmek gerekir, yoksa "yazdım ama görünmüyor"
        // diye 10 dakika kaybedilir.
        if (app()->isLocal()) {
            return $this->oku();
        }

        /*
         * ÖNBELLEĞE DÜZ DİZİ YAZILIR, NESNE DEĞİL (2026-08-05 canlı hatası).
         *
         * Eskiden Collection<RehberSayfasi> serileştiriliyordu ve canlıda
         * `__PHP_Incomplete_Class` dönüyordu — serileştirilmiş nesne, sınıfın
         * o anki şekline bağımlıdır.
         *
         * Bu hata YERELDE GÖRÜNMEZDİ: yukarıdaki `isLocal()` dalı yüzünden
         * önbellek yolu testlerde hiç çalışmıyordu. Kırılan yol, test
         * edilmeyen yoldu — o yüzden artık ayrı bir test doğrudan bu dalı
         * zorluyor (ElKitabiOnbellekTest).
         *
         * Beklenmedik bir şey okunursa (eski sürümden kalan nesne, bozuk
         * satır) SESSİZCE YENİDEN HESAPLANIR: El Kitabı'nın açılmaması,
         * önbelleğin ıskalanmasından çok daha pahalı.
         */
        $ham = Cache::get(self::CACHE_ANAHTARI);

        if (is_array($ham)) {
            return collect($ham)
                ->filter(fn ($satir) => is_array($satir))
                ->map(fn (array $satir) => RehberSayfasi::fromDizi($satir))
                ->values();
        }

        $sayfalar = $this->oku();

        Cache::put(
            self::CACHE_ANAHTARI,
            $sayfalar->map(fn (RehberSayfasi $s) => $s->toDizi())->all(),
            self::CACHE_SANIYE,
        );

        return $sayfalar;
    }

    public function bul(string $slug): ?RehberSayfasi
    {
        return $this->tumSayfalar()->firstWhere('slug', $slug);
    }

    /**
     * Bir Filament ekranının rehber sayfası ("Yardım" düğmesi için).
     *
     * Bağ SINIF ADINDAN kurulur, elle yazılmış bir eşleme tablosundan değil:
     * sınıf yeniden adlandırılınca bağ sessizce kopmaz, test yakalar.
     */
    public function ekranIcin(string $sinif): ?RehberSayfasi
    {
        return $this->tumSayfalar()->firstWhere('ekran', ltrim($sinif, '\\'));
    }

    /**
     * Basit tam metin araması.
     *
     * Sıralama bilinçli: BAŞLIKTA geçen sayfa, gövdesinde geçenden önce gelir.
     * "yedek" araması "Yedekleme ve kurtarma"yı, içinde bir kez "yedek" geçen
     * uzun bir sayfadan önce göstermeli.
     *
     * @return Collection<int, RehberSayfasi>
     */
    public function ara(string $sorgu): Collection
    {
        $sorgu = mb_strtolower(trim($sorgu));

        if ($sorgu === '') {
            return $this->tumSayfalar();
        }

        return $this->tumSayfalar()
            ->filter(fn (RehberSayfasi $s) => str_contains($s->aranabilirMetin(), $sorgu))
            ->sortBy(fn (RehberSayfasi $s) => str_contains(mb_strtolower($s->baslik), $sorgu) ? 0 : 1)
            ->values();
    }

    /**
     * Kâhya'nın yönergesine giren dizin (başlık + özet, gövde YOK).
     *
     * Gövdeleri yönergeye basmak bağlam penceresini şişirir ve panel haritası,
     * hafıza, görevler gibi asıl bağlamı dışarı iter. Kâhya bu dizinden hangi
     * sayfanın ilgili olduğunu seçer, tam metni `rehber-oku` aracıyla ister.
     */
    public function yonergeDizini(): string
    {
        $sayfalar = $this->tumSayfalar();

        if ($sayfalar->isEmpty()) {
            return 'Rehber sayfası yok.';
        }

        return $sayfalar->map(fn (RehberSayfasi $s) => $s->yonergeSatiri())->implode("\n");
    }

    /** @return Collection<int, RehberSayfasi> */
    private function oku(): Collection
    {
        $klasor = base_path('docs/rehber');

        if (! is_dir($klasor)) {
            return collect();
        }

        $sayfalar = collect(glob($klasor.'/*.md') ?: [])
            ->map(fn (string $yol) => $this->ayristir($yol))
            ->filter()
            ->values();

        return $sayfalar
            ->sortBy([['sira', 'asc'], ['baslik', 'asc']])
            ->values();
    }

    private function ayristir(string $yol): ?RehberSayfasi
    {
        $ham = (string) file_get_contents($yol);
        $slug = basename($yol, '.md');

        if (! preg_match('/^---\R(.*?)\R---\R(.*)$/s', $ham, $eslesme)) {
            // Front-matter'sız dosya SESSİZCE ATLANMAZ olmalıydı, ama burada
            // atlanıyor: rehber klasörüne konan bir README ya da taslak yüzünden
            // El Kitabı'nın tamamen kırılması, o dosyanın görünmemesinden kötü.
            return null;
        }

        $ust = $this->frontMatter($eslesme[1]);

        return new RehberSayfasi(
            slug: $slug,
            baslik: $ust['baslik'] ?? Str::headline($slug),
            ozet: $ust['ozet'] ?? '',
            govde: trim($eslesme[2]),
            sira: (int) ($ust['sira'] ?? 999),
            ekran: isset($ust['ekran']) && $ust['ekran'] !== '' ? ltrim($ust['ekran'], '\\') : null,
            etiketler: isset($ust['etiketler'])
                ? array_values(array_filter(array_map('trim', explode(',', $ust['etiketler']))))
                : [],
        );
    }

    /** @return array<string, string> */
    private function frontMatter(string $blok): array
    {
        $alanlar = [];

        foreach (preg_split('/\R/', $blok) ?: [] as $satir) {
            if (! str_contains($satir, ':')) {
                continue;
            }

            [$anahtar, $deger] = explode(':', $satir, 2);
            $alanlar[trim($anahtar)] = trim($deger);
        }

        return $alanlar;
    }
}
