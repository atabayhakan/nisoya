<?php

namespace App\Ai\Kahya;

use App\Ai\Kahya\Araclar\EylemAraci;
use App\Ai\Kahya\Araclar\TabloSorgula;
use App\Models\Category;
use App\Models\KahyaMesaji;
use App\Models\User;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\PanelHaritasi;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Kâhya'nın laravel/ai ajan gövdesi — F0 "beyin nakli"
 * (bkz. docs/plans/2026-07-30-kahya-yuksek-dereceli-asistan-design.md).
 *
 * Eski model (tek çağrı → JSON karar → tek eylem) emekli oldu. Artık model
 * bir ARAÇ DÖNGÜSÜNDE koşar: bakması gerekeni tablo-sorgula ile bakar,
 * yapması gerekeni eylem araçlarıyla yapar, sonucu görür, gerekirse devam
 * eder, en sonunda sahibe Türkçe cevabını yazar.
 *
 * Yan kazanım: response_format:json_object hilesi ve onun "json kelimesi" /
 * "geçersiz JSON" hata sınıfı bu dosyayla birlikte tarihe karıştı — araç
 * çağrıları sağlayıcının NATIVE tool-calling API'siyle akar.
 *
 * Güvenlik modeli DEĞİŞMEDİ: modelin elinde SQL yok; okuma izin listeli
 * `tablo-sorgula`dan, yazma denetim-izli eylem araçlarından geçer.
 */
#[MaxSteps(12)]
class KahyaAjani implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  Collection<int, KahyaMesaji>  $gecmis  bu turdan ÖNCEKİ mesajlar
     *                                                (eski→yeni sıralı; şu anki
     *                                                mesaj prompt olarak gider,
     *                                                burada TEKRARLANMAZ)
     */
    public function __construct(
        private readonly KahyaTeshisi $teshis,
        private readonly BekleyenIsler $bekleyen,
        private readonly PanelHaritasi $harita,
        private readonly EylemKatalogu $katalog,
        private readonly EylemCalistirici $calistirici,
        private readonly EylemToplayici $toplayici,
        private readonly Collection $gecmis,
        private readonly ?User $sahip = null,
    ) {}

    public function instructions(): Stringable|string
    {
        $envanter = $this->teshis->gercekEnvanter();
        $kuyruklar = $this->bekleyen->topla();

        $kuyrukMetni = $kuyruklar === []
            ? 'Bekleyen iş yok.'
            : implode("\n", array_map(fn (array $k): string => "- {$k['etiket']}: {$k['adet']}", $kuyruklar));

        return <<<METIN
        Sen "Kâhya"sın: Nisoya'nın (yurtdışındaki Türkler için ücretsiz Türkçe pazaryeri)
        yönetim asistanısın. Sahibiyle Türkçe, kısa ve doğrudan konuşursun. Yağcılık yapmaz,
        gereksiz özet çıkarmazsın.

        ## Sitenin şu anki durumu
        Aktif ilan: {$envanter['ilan']} · Benzersiz satıcı: {$envanter['satici']}
        Bekleyen işler:
        {$kuyrukMetni}

        ## Site kimliği (SEO ve metin yazarken buradan beslen)
        {$this->siteKimligi()}

        ## Panel haritası (yol tarifi için)
        Sahip bir ekranın ya da özelliğin NEREDE olduğunu sorarsa buradan cevapla:
        sol menüdeki grup adını, ekran adını ve adresini söyle. Haritada olmayan bir
        yeri tarif etme.

        {$this->harita->metin()}

        ## Kurallar
        1. Sana verilen araçların DIŞINDA hiçbir iş yapamazsın; veritabanına doğrudan erişimin yok.
        2. Bir eylem için id/kod gerekiyorsa (üst kategori, ülke kodu...) UYDURMA — önce
           tablo-sorgula ile bak, bulduğun gerçek değerle eylemi çağır.
        3. Sahip yalnızca soru soruyorsa araç çağırmadan cevapla; iş istiyorsa uygun aracı kullan.
        4. İstenen iş araçlarında yoksa yapamadığını söyle; panelde elle yapılabiliyorsa
           panel haritasından yerini tarif et.
        5. Araç sonucu GERÇEKTİR, senin niyetin değil: "BAŞARILI" görmediysen yaptım deme,
           "ONAY BEKLİYOR" gördüysen sahibin onay kartını beklediğini söyle.
        6. Emin değilsen sor. Yanlış iş yapmak, sormaktan pahalıdır.
        7. Son cevabın kısa ve Türkçe olsun: ne yaptın / ne buldun / sahipten ne bekliyorsun.
        METIN;
    }

    /** @return list<Message> */
    public function messages(): iterable
    {
        return $this->gecmis
            ->map(fn (KahyaMesaji $m): Message => new Message(
                $m->rol === KahyaMesaji::ROL_SAHIP ? 'user' : 'assistant',
                $m->metin,
            ))
            ->values()
            ->all();
    }

    /** @return list<Tool> */
    public function tools(): iterable
    {
        $araclar = [new TabloSorgula];

        foreach ($this->katalog->hepsi() as $eylem) {
            $araclar[] = new EylemAraci($eylem, $this->calistirici, $this->toplayici, $this->sahip);
        }

        return $araclar;
    }

    /**
     * Sitenin kimlik kartı — seo-doldur gibi metin üreten eylemlerin malzemesi
     * (KahyaSohbeti'nin eski siteKimligi'sinden taşındı).
     */
    private function siteKimligi(): string
    {
        $ad = (string) Settings::get('genel.site_adi', 'Nisoya');
        $baslik = (string) Settings::get('seo.default_title', '');
        $aciklama = (string) Settings::get('seo.default_description', '');

        $kategoriler = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');

        return "Site adı: {$ad}\n"
            ."Mevcut SEO başlığı: \"{$baslik}\"\n"
            ."Mevcut SEO açıklaması: \"{$aciklama}\"\n"
            .'Ana kategoriler: '.($kategoriler !== '' ? $kategoriler : '(henüz yok)');
    }
}
