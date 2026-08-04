<?php

namespace App\Ai\Kahya;

use App\Ai\Kahya\Araclar\EylemAraci;
use App\Ai\Kahya\Araclar\IsletmeKesfet;
use App\Ai\Kahya\Araclar\PanelYonlendir;
use App\Ai\Kahya\Araclar\RehberOku;
use App\Ai\Kahya\Araclar\TabloSorgula;
use App\Ai\Kahya\Araclar\WebAra;
use App\Models\BekleyenHamle;
use App\Models\Category;
use App\Models\KahyaGorevi;
use App\Models\KahyaMesaji;
use App\Models\User;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\PanelHaritasi;
use App\Services\Rehber\ElKitabiRehberi;
use App\Services\Rehber\RehberBoslukAvcisi;
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
        private readonly YonlendirmeToplayici $yonlendirici,
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

        $isim = config('kahya.isim', 'Kâhya');

        // Yönergedeki "bilmiyorum" ifadesi ile RehberBoslukAvcisi'nin aradığı
        // ifade AYNI SABİTTEN gelir. İkisi kayarsa avcı sessizce körelir ve
        // rehber boşlukları hiç görünmez.
        $isaret = RehberBoslukAvcisi::ISARET;

        return <<<METIN
        Sen "{$isim}"sın: Nisoya'nın (yurtdışındaki Türkler için ücretsiz Türkçe pazaryeri)
        yönetim asistanısın. Sahibiyle Türkçe, kısa ve doğrudan konuşursun. Yağcılık yapmaz,
        gereksiz özet çıkarmazsın.

        ## Sitenin şu anki durumu
        Aktif ilan: {$envanter['ilan']} · Benzersiz satıcı: {$envanter['satici']}
        Bekleyen işler:
        {$kuyrukMetni}

        ## Site kimliği (SEO ve metin yazarken buradan beslen)
        {$this->siteKimligi()}

        ## Hatırladıkların (kalıcı hafızan — kurallara UY, gerçeklere GÜVEN)
        {$this->hafizaMetni()}

        ## Görevlerin (görev defteri — uzun vadeli misyonlar)
        {$this->gorevMetni()}

        ## Panel haritası (yol tarifi için)
        Sahip bir ekranın ya da özelliğin NEREDE olduğunu sorarsa buradan cevapla:
        sol menüdeki grup adını ve ekran adını söyle, ayrıca `panel-yonlendir` aracını
        haritadaki adresle çağır — ekran menüde vurgulanır ve cevabına tek tıkla giden
        bir "Aç" düğmesi eklenir. Ham adresi cevabına yazma. Haritada olmayan bir yeri
        tarif etme, adres uydurma.

        {$this->harita->metin()}

        ## El Kitabı rehberi (NASIL YAPILIR sorularının TEK kaynağı)
        Aşağıda rehber sayfalarının dizini var. Sahip "nasıl yaparım", "ne oluyor",
        "neden böyle" gibi bir şey sorduğunda ÖNCE `rehber-oku` aracını ilgili
        slug'la çağır ve cevabını O SAYFADAN ALINTIYLA ver.

        KAYNAK GÖSTEREMİYORSAN CEVAP VERME. Rehberde o konu yoksa cevabında AYNEN
        şu ifadeyi kullan: "{$isaret}" — ve varsa panel haritasından yerini tarif
        et. Bu ifade bir sinyaldir: rehberdeki boşlukları senin cevaplarından
        toplayan bir mekanizma var, başka kelimelerle söylersen boşluk görünmez
        kalır ve o sayfa hiç yazılmaz. Sahip tek kişi;
        yanlışını yakalayacak ikinci bir kullanıcı yok, bu yüzden alıntı zorunlu.
        Genel bilgiden ya da kendi tahmininden panel davranışı anlatma.

        {$this->rehberDizini()}

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
        8. Sahip "hatırla/unutma/bundan sonra hep..." derse `hatirla` aracını, "unut/artık
           geçersiz" derse `unut` aracını kullan. Hatırladıkların bölümüne sığmayan eski
           kayıtları tablo-sorgula ile kahya_hafiza tablosunda arayabilirsin.
        9. Haftalar sürecek bir iş istenirse `gorev-ac` ile deftere yaz (planı sen tasarla);
           bir görevde ne zaman ilerleme olsa `gorev-guncelle` ile İŞLE. Sistemden DIŞARI
           çıkacak her iş (e-posta, tanıtım, sosyal içerik) için tek yolun `hamle-oner`:
           bitmiş bir taslak yaz, kartı bırak, kararın sahibde olduğunu söyle.
        10. Güncel/dış bilgi için `web-ara`, işletme keşfi için `isletme-kesfet` kullan —
           ikisi de aylık kredili: gereksiz tekrar sorgu atma, bulduğunu görevin notuna işle.
           Araç "YAPILANDIRILMAMIŞ" ya da "LİMİT DOLDU" derse bunu sahibe aynen aktar.
        11. E-posta hamlesi önerirken alici_eposta ZORUNLU ve GERÇEK olmalı (web-ara ya da
           isletme-kesfet ile bulunmuş; ASLA uydurma/tahmin). Sahip kartı onaylarsa mesaj
           Kâhya'nın gönderim kimliğiyle OTOMATİK gönderilir — taslağı gönderilmeye hazır
           yaz. Engel listesindeki (kahya_gonderim_engelleri) adrese hamle önerme.
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

    /**
     * Rehber sayfalarının DİZİNİ (gövde değil) — yönergeye giren kısım.
     *
     * Konteynerden çözülüyor, kurucudan değil: bu sınıfın kurucusu zaten dokuz
     * parametreli ve çağrı yeri konumsal argüman kullanıyor; onuncuyu eklemek
     * KahyaSohbeti::ajan()'ı da değiştirmeyi gerektirirdi. Araçlarda (WebAra,
     * IsletmeKesfet) zaten aynı `app()` deseni var.
     */
    private function rehberDizini(): string
    {
        return app(ElKitabiRehberi::class)->yonergeDizini();
    }

    /** @return list<Tool> */
    public function tools(): iterable
    {
        $araclar = [
            new TabloSorgula,
            new PanelYonlendir($this->harita, $this->yonlendirici),
            // El Kitabı rehberi — "nasıl yaparım" sorularının kaynağı.
            // Yönergede yalnız dizin var; tam metin bu araçla gelir.
            app(RehberOku::class),
            // Dış gözler (F3) — anahtar yokken de kayıtlı: model sahibe
            // "şurayı yapılandır" tarifini ancak aracı görürse verebilir.
            app(WebAra::class),
            app(IsletmeKesfet::class),
        ];

        foreach ($this->katalog->hepsi() as $eylem) {
            $araclar[] = new EylemAraci($eylem, $this->calistirici, $this->toplayici, $this->sahip);
        }

        return $araclar;
    }

    /**
     * Kalıcı hafızanın yönergedeki hâli (F1 — tasarım §2.3).
     *
     * Her satır id taşır: sahip "12'yi unut" diyebilsin, model `unut` için
     * id aramak zorunda kalmasın. Tavan {@see KahyaHafizasi::yonergeIcin}'de —
     * kural/gerçek önce, taşan ders/not tablo-sorgula ile aranır.
     */
    private function hafizaMetni(): string
    {
        $kayitlar = \App\Models\KahyaHafizasi::yonergeIcin();

        if ($kayitlar->isEmpty()) {
            return '(Hafızan henüz boş. Sahip kalıcı bir şey söylerse `hatirla` ile yaz.)';
        }

        return $kayitlar
            ->map(fn (\App\Models\KahyaHafizasi $k): string => "- [{$k->id}·{$k->tur->etiket()}] {$k->metin}")
            ->implode("\n");
    }

    /**
     * Görev defterinin yönergedeki hâli (F2 — tasarım §2.3).
     *
     * Yalnız AÇIK görevler girer (kapalıların yeri tablo-sorgula); her görev
     * id + ilerleme + sıradaki adım + son not taşır — model "neredeyiz"i
     * sormadan bilsin. Bekleyen hamle sayısı da burada: model aynı hamleyi
     * ikinci kez önermesin.
     */
    private function gorevMetni(): string
    {
        $gorevler = KahyaGorevi::query()->acik()->latest('id')->limit(10)->get();
        $bekleyenHamle = BekleyenHamle::query()->beklemede()->count();

        if ($gorevler->isEmpty() && $bekleyenHamle === 0) {
            return '(Açık görev yok. Sahip uzun vadeli bir iş isterse `gorev-ac` ile defter tut.)';
        }

        $satirlar = $gorevler->map(function (KahyaGorevi $g): string {
            $ilerleme = $g->ilerleme();
            $siradaki = $g->siradakiAdim();
            $sonNot = collect($g->ilerleme_notlari ?? [])->last();

            return "- [#{$g->id}] {$g->baslik} ({$ilerleme['yapildi']}/{$ilerleme['toplam']} adım)"
                .($siradaki !== null ? " · sıradaki: {$siradaki}" : ' · plandaki tüm adımlar işlendi')
                .($sonNot !== null ? " · son not: {$sonNot['not']}" : '');
        });

        if ($bekleyenHamle > 0) {
            $satirlar->push("- Onay kuyruğunda {$bekleyenHamle} hamle kartı sahibin kararını bekliyor — aynısını tekrar önerme.");
        }

        return $satirlar->implode("\n");
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
