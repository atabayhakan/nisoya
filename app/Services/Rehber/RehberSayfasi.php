<?php

namespace App\Services\Rehber;

use Illuminate\Support\Str;

/**
 * El Kitabı'nın tek bir rehber sayfası (docs/rehber/*.md).
 *
 * Sayfa dört yüzeye birden hizmet eder (bkz. plan 2026-08-04):
 *   · El Kitabı ekranı — kenar çubuğu + arama + içerik
 *   · Ekran içi "Yardım" slide-over'ı — `ekran` alanı üzerinden bağlanır
 *   · Kâhya — soruyu bu metinden ALINTIYLA yanıtlar
 *   · Belgeler — genel bakış/yatırımcı çıktısının kaynağı
 *
 * Bu yüzden metin TEK yerde yaşar: markdown. Kod içine gömülü ikinci bir
 * anlatım olursa üçüncü ayda ikisi çelişir.
 */
class RehberSayfasi
{
    /**
     * @param  list<string>  $etiketler
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $baslik,
        public readonly string $ozet,
        public readonly string $govde,
        public readonly int $sira,
        public readonly ?string $ekran,
        public readonly array $etiketler,
    ) {}

    /** Markdown gövdesinin HTML'i (El Kitabı ekranı ve slide-over için). */
    public function html(): string
    {
        return Str::markdown($this->govde);
    }

    /**
     * Arama için düzleştirilmiş metin — başlık, özet, etiketler ve gövde.
     * Küçük harfe indirgenir; Türkçe için mb_strtolower şart (I/İ).
     */
    public function aranabilirMetin(): string
    {
        return mb_strtolower(implode(' ', [
            $this->baslik,
            $this->ozet,
            implode(' ', $this->etiketler),
            $this->govde,
        ]));
    }

    /**
     * Kâhya'nın yönergesine giren kısa biçim.
     *
     * TAM GÖVDE GİRMEZ: on sayfanın tamamı yönergeyi şişirir ve asıl bağlamı
     * (panel haritası, hafıza, görevler) dışarı iter. Kâhya başlık+özetten
     * hangi sayfanın ilgili olduğunu seçer, sonra `rehber-oku` aracıyla tam
     * metni ister.
     */
    public function yonergeSatiri(): string
    {
        return sprintf('- `%s` — %s: %s', $this->slug, $this->baslik, $this->ozet);
    }
}
