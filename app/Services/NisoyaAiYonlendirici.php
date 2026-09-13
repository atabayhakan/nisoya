<?php

namespace App\Services;

use App\Contracts\AiProvider;
use App\Enums\JobStatus;
use App\Enums\ListingStatus;
use App\Models\Country;
use App\Models\IslemTuru;
use App\Models\JobListing;
use App\Models\Listing;
use App\Models\YasamKategorisi;
use App\Support\Para;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Anasayfa "Nisoya AI ile ara" çubuğu ve Command Palette (⌘K) akıllı yönlendiricisi.
 *
 * ---------------------------------------------------------------------------
 * YÖNLENDİRİR, DOĞRULAR, AKILLI DIASPORA CEVAPLARI VERİR
 *
 * Kullanıcının serbest metinle yazdığı soruyu hızlı kuralcı eşleştirici ve
 * tekil AI çağrısıyla sınıflandırır (rehber/yasam/spor/is/ilan/eylem/sss/kapsam_disi/belirsiz).
 *
 * Halı Saha & Futbol sorgularında ("maç", "takım kur", "halı saha"):
 * Kullanıcının ülkesine/şehrine göre gerçek maç ve takımları listeler.
 * Eğer o ülkede henüz maç veya takım oluşturulmamışsa kuru bir 'bulunamadı'
 * yerine, topluluğu başlatan akıllı diaspora yanıtı ("İlk Takımı Sen Kur",
 * "Maç İlanı Ver", vb.) üretir.
 */
class NisoyaAiYonlendirici
{
    private const ONBELLEK_SURESI_GUN = 7;

    private const DOLGU_KELIMELER = [
        'iyi', 'merhaba', 'selam', 'selamlar', 'naber', 'nasilsin', 'nasılsın',
        'hey', 'hello', 'hi', 'slm', 'mrb', 'test', 'deneme', 'sa', 'as',
    ];

    public function __construct(
        private readonly AiProvider $ai,
        private readonly RehberDogalDilArama $rehberArama,
        private readonly YasamDogalDilArama $yasamArama,
        private readonly SssDogalDilArama $sssArama,
        private readonly SporDogalDilArama $sporArama,
        private readonly RehberYuzeyi $rehberYuzeyi,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.features.nisoya_ai_arama') && $this->ai->isConfigured();
    }

    /**
     * Anlamsız tekil selamlama veya tek harfli girdileri filtreler;
     * 'maç', 'iş', 'pasaport' gibi tek kelimelik alan terimlerini kabul eder.
     */
    public function aranmaliMi(string $sorgu): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $sorgu = trim($sorgu);
        if ($sorgu === '' || mb_strlen($sorgu) < 2) {
            return false;
        }

        $kelimeler = array_values(array_filter(preg_split('/\s+/u', $sorgu) ?: []));

        if (count($kelimeler) === 1 && in_array(mb_strtolower($kelimeler[0]), self::DOLGU_KELIMELER, true)) {
            return false;
        }

        return true;
    }

    /**
     * @return array{
     *     niyet: string,
     *     baslik?: ?string,
     *     mesaj?: ?string,
     *     oneri?: ?string,
     *     eylemler?: list<array{baslik: string, url: string, stil: string, ikon?: string}>,
     *     ulke?: ?array{kod: ?string, ad: string, emoji: string},
     *     sonuclar: Collection<int, array{baslik: string, altbaslik: string, url: string}>,
     *     ilanBaglantisi: ?string
     * }
     */
    public function ara(string $sorgu, ?string $varsayilanUlkeKodu): array
    {
        $yorum = $this->yorumla($sorgu, $varsayilanUlkeKodu);

        // 1. Spor & Futbol Ekosistemi
        if ($yorum['niyet'] === 'spor') {
            $sporSonuc = $this->sporArama->ara(
                $yorum['ulke_kodu'],
                $yorum['sehir'] ?? null,
                $yorum['anahtar_kelimeler'],
                $varsayilanUlkeKodu
            );

            return [
                'niyet' => 'spor',
                'baslik' => $sporSonuc['baslik'],
                'mesaj' => $sporSonuc['mesaj'],
                'oneri' => $sporSonuc['oneri'],
                'eylemler' => $sporSonuc['eylemler'],
                'ulke' => [
                    'kod' => $sporSonuc['ulke_kodu'],
                    'ad' => $sporSonuc['ulke_adi'],
                    'emoji' => $sporSonuc['ulke_emoji'],
                ],
                'sonuclar' => $sporSonuc['sonuclar'],
                'ilanBaglantisi' => $sporSonuc['ilanBaglantisi'],
            ];
        }

        // 2. Doğrudan Platform Eylemleri
        if ($yorum['niyet'] === 'eylem') {
            $eylemTuru = $yorum['eylem_turu'] ?? 'genel';

            return $this->eylemSonucu($eylemTuru, $varsayilanUlkeKodu);
        }

        // 3. İş & Kariyer İlanları
        if ($yorum['niyet'] === 'is') {
            $hedefUlke = $this->dogrulaUlke($yorum['ulke_kodu']) ?? $this->dogrulaUlke($varsayilanUlkeKodu);
            $country = $hedefUlke ? Country::query()->where('code', $hedefUlke)->first() : null;
            $ulkeAdi = $country ? $country->name_tr : 'bulunduğunuz ülkede';
            $ulkeEmoji = $country ? $country->emoji : '🌍';

            // Gerçek aktif iş ilanlarını sorgula
            $queryBuilder = JobListing::query()
                ->where('status', JobStatus::Aktif)
                ->when($hedefUlke, fn ($q) => $q->where('country_code', $hedefUlke));

            $filtreKelimeler = array_values(array_filter(
                $yorum['anahtar_kelimeler'],
                fn (string $k) => ! in_array(mb_strtolower(trim($k)), ['iş', 'is', 'ilan', 'ilani', 'ilanı', 'ilanları', 'işler', 'kariyer', 'bul'], true)
            ));

            if (! empty($filtreKelimeler)) {
                $queryBuilder->where(function ($sub) use ($filtreKelimeler) {
                    foreach ($filtreKelimeler as $kelime) {
                        $sub->orWhere('title', 'like', "%{$kelime}%")
                            ->orWhere('description', 'like', "%{$kelime}%");
                    }
                });
            }

            $isler = $queryBuilder->with(['company', 'country'])->latest()->take(5)->get();

            /** @var Collection<int, array{baslik: string, altbaslik: string, url: string}> $sonuclar */
            $sonuclar = collect();

            foreach ($isler as $is) {
                $sehir = $is->city ?: ($is->country ? $is->country->name_tr : 'Yurt Dışı');
                $sirket = $is->company ? $is->company->name : 'Nisoya Üyesi';
                $calismaTuru = $is->employment_type->getLabel();
                $maas = $is->salary_min ? ' • '.Para::bicimle($is->salary_min).' '.($is->salary_currency ?: '') : '';

                $sonuclar->push([
                    'baslik' => "💼 {$is->title}",
                    'altbaslik' => "{$sirket} • {$sehir} • {$calismaTuru}{$maas}",
                    'url' => route('jobs.show', [$is->id, $is->slug]),
                ]);
            }

            $varMi = $sonuclar->isNotEmpty();

            if ($varMi) {
                $baslik = "💼 {$ulkeEmoji} {$ulkeAdi} İş & Kariyer Fırsatları";
                $mesaj = "{$ulkeAdi} genelinde '{$sorgu}' ile eşleşen {$sonuclar->count()} aktif iş fırsatı bulundu.";
                $oneri = 'İlan detaylarını inceleyip doğrudan başvurabilir veya kendi şirketiniz için iş ilanı verebilirsiniz.';
                $eylemler = [
                    ['baslik' => '💼 Yeni İş İlanı Yayınla', 'url' => route('panel.jobs.create'), 'stil' => 'primary', 'ikon' => 'briefcase'],
                    ['baslik' => '🔍 Tüm İş İlanlarını İncele', 'url' => $this->isBaglantisi($sorgu, $hedefUlke), 'stil' => 'outline', 'ikon' => 'magnifying-glass'],
                ];
            } else {
                $baslik = "💼 {$ulkeEmoji} {$ulkeAdi} için Henüz Aktif İş İlanı Bulunmuyor";
                $mesaj = "Şu anda {$ulkeAdi} bölgesinde bu alanda yayınlanmış aktif bir iş veya eleman ilanı bulunmuyor.";
                $oneri = 'Diasporadaki ilk iş fırsatını siz oluşturabilir ve binlerce Türkçe konuşan üyeye ulaşabilirsiniz!';
                $eylemler = [
                    ['baslik' => '💼 İlk İş İlanını Sen Yayınla', 'url' => route('panel.jobs.create'), 'stil' => 'primary', 'ikon' => 'plus'],
                    ['baslik' => '🌍 Tüm Ülkelerdeki İşleri Gör', 'url' => route('jobs.index'), 'stil' => 'secondary', 'ikon' => 'briefcase'],
                ];
            }

            return [
                'niyet' => 'is',
                'baslik' => $baslik,
                'mesaj' => $mesaj,
                'oneri' => $oneri,
                'eylemler' => $eylemler,
                'ulke' => ['kod' => $hedefUlke, 'ad' => $ulkeAdi, 'emoji' => $ulkeEmoji],
                'sonuclar' => $sonuclar,
                'ilanBaglantisi' => $this->isBaglantisi($sorgu, $hedefUlke),
            ];
        }

        // 4. Seri İlanlar & Hizmet Pazarı
        if ($yorum['niyet'] === 'ilan') {
            $hedefUlke = $this->dogrulaUlke($yorum['ulke_kodu']) ?? $this->dogrulaUlke($varsayilanUlkeKodu);
            $country = $hedefUlke ? Country::query()->where('code', $hedefUlke)->first() : null;
            $ulkeAdi = $country ? $country->name_tr : 'Tüm Ülkeler';
            $ulkeEmoji = $country ? $country->emoji : '🌍';

            // Gerçek aktif ilanları sorgula
            $queryBuilder = Listing::query()
                ->where('status', ListingStatus::Aktif)
                ->when($hedefUlke, fn ($q) => $q->where('country_code', $hedefUlke));

            $filtreKelimeler = array_values(array_filter(
                $yorum['anahtar_kelimeler'],
                fn (string $k) => ! in_array(mb_strtolower(trim($k)), ['ilan', 'ilanlar', 'ilanları', 'fiyat', 'fiyatı', 'arıyorum', 'var'], true)
            ));

            if (! empty($filtreKelimeler)) {
                $queryBuilder->where(function ($sub) use ($filtreKelimeler) {
                    foreach ($filtreKelimeler as $kelime) {
                        $sub->orWhere('title', 'like', "%{$kelime}%")
                            ->orWhere('description', 'like', "%{$kelime}%");
                    }
                });
            }

            $ilanlar = $queryBuilder->with(['country'])->latest()->take(5)->get();

            /** @var Collection<int, array{baslik: string, altbaslik: string, url: string}> $sonuclar */
            $sonuclar = collect();

            foreach ($ilanlar as $ilan) {
                $sehir = $ilan->city ?: ($ilan->country ? $ilan->country->name_tr : 'Yurt Dışı');
                $fiyat = $ilan->price ? Para::bicimle($ilan->price).' '.($ilan->currency ?: '') : 'Fiyat Belirtilmemiş';

                $sonuclar->push([
                    'baslik' => "📢 {$ilan->title}",
                    'altbaslik' => "{$sehir} • {$fiyat}",
                    'url' => route('listings.show', [$ilan->id, $ilan->slug]),
                ]);
            }

            $varMi = $sonuclar->isNotEmpty();

            if ($varMi) {
                $baslik = "📢 {$ulkeEmoji} {$ulkeAdi} İlan & Hizmet Pazarı";
                $mesaj = "{$ulkeAdi} bölgesinde '{$sorgu}' araması için {$sonuclar->count()} pazar yeri kaydı bulundu.";
                $oneri = 'İlan detaylarını inceleyebilir veya siz de ilan vererek Türkçe konuşan topluluğa ulaşabilirsiniz.';
                $eylemler = [
                    ['baslik' => '📝 Yeni İlan Ver', 'url' => url('/ilan-ver'), 'stil' => 'primary', 'ikon' => 'plus'],
                    ['baslik' => '🔎 Tüm İlanları Filtrele', 'url' => $this->ilanBaglantisi($sorgu), 'stil' => 'outline', 'ikon' => 'arrow-right'],
                ];
            } else {
                $baslik = "📢 {$ulkeEmoji} {$ulkeAdi} Bölgesinde Henüz İlan Bulunmuyor";
                $mesaj = "Şu anda {$ulkeAdi} pazarında '{$sorgu}' ile ilgili yayınlanmış aktif bir ilan bulunamadı.";
                $oneri = "İlk ilanı veya hizmet talebini siz oluşturarak {$ulkeAdi} diaspora topluluğundan hızlıca yanıt alabilirsiniz!";
                $eylemler = [
                    ['baslik' => '📝 İlk İlanı Sen Ver', 'url' => url('/ilan-ver'), 'stil' => 'primary', 'ikon' => 'plus'],
                    ['baslik' => '🌐 Tüm Pazar Yerinde Ara', 'url' => $this->ilanBaglantisi($sorgu), 'stil' => 'outline', 'ikon' => 'magnifying-glass'],
                ];
            }

            return [
                'niyet' => 'ilan',
                'baslik' => $baslik,
                'mesaj' => $mesaj,
                'oneri' => $oneri,
                'eylemler' => $eylemler,
                'ulke' => ['kod' => $hedefUlke, 'ad' => $ulkeAdi, 'emoji' => $ulkeEmoji],
                'sonuclar' => $sonuclar,
                'ilanBaglantisi' => $this->ilanBaglantisi($sorgu),
            ];
        }

        // 5. Kapsam Dışı Konsolosluk Uyarısı
        if ($yorum['niyet'] === 'kapsam_disi') {
            return [
                'niyet' => 'kapsam_disi',
                'baslik' => 'ℹ️ T.C. Konsolosluk Yetki Alanı Dışında',
                'mesaj' => 'Bu işlem T.C. konsolosluklarının yetki alanında değildir. Üçüncü bir ülkeye vize veya ikamet başvurusu için ilgili ülkenin kendi göç idaresi / konsolosluğuna başvurmanız gerekir.',
                'oneri' => 'T.C. vatandaşlarının pasaport, vekaletname, askerlik ve noter işlemleri için resmî konsolosluk rehberimizi inceleyebilirsiniz.',
                'eylemler' => [
                    ['baslik' => '🏛️ Resmî Konsolosluk Rehberi', 'url' => url('/rehber'), 'stil' => 'primary', 'ikon' => 'building-library'],
                ],
                'sonuclar' => collect(),
                'ilanBaglantisi' => null,
            ];
        }

        // 6. Platform SSS
        if ($yorum['niyet'] === 'sss') {
            $sonuclar = $this->sssArama->ara($yorum['anahtar_kelimeler']);

            if ($sonuclar->isNotEmpty()) {
                return [
                    'niyet' => 'sss',
                    'baslik' => '💡 Sıkça Sorulan Sorular',
                    'mesaj' => 'Nisoya platformu ile ilgili aradığınız cevaplar:',
                    'sonuclar' => $sonuclar,
                    'ilanBaglantisi' => null,
                ];
            }

            return $this->linkSonucu('belirsiz', $this->ilanBaglantisi($sorgu));
        }

        // 7. Rehber / Yaşam Rehberi
        $rehberVarsayilan = $yorum['niyet'] === 'rehber' ? $varsayilanUlkeKodu : null;
        $yasamVarsayilan = $yorum['niyet'] === 'yasam' ? $varsayilanUlkeKodu : null;

        $sira = $yorum['niyet'] === 'yasam' ? ['yasam', 'rehber'] : ['rehber', 'yasam'];

        foreach ($sira as $motor) {
            $sonuclar = $motor === 'rehber'
                ? $this->rehberArama->ara($yorum['ulke_kodu'], $yorum['islem_turu_slug'], $yorum['anahtar_kelimeler'], $rehberVarsayilan)
                : $this->yasamArama->ara($yorum['ulke_kodu'], $yorum['yasam_kategori_slug'], $yorum['anahtar_kelimeler'], $yasamVarsayilan);

            if ($sonuclar->isNotEmpty()) {
                $motorAdi = $motor === 'rehber' ? 'Resmî Konsolosluk Rehberi' : 'Gündelik Yaşam Rehberi';

                return [
                    'niyet' => $motor,
                    'baslik' => "✓ {$sonuclar->count()} Doğrulanmış {$motorAdi} Kaynağı",
                    'mesaj' => 'İşleminizle ilgili resmî mevzuat ve evrak adımları aşağıda sıralanmıştır.',
                    'sonuclar' => $sonuclar,
                    'ilanBaglantisi' => null,
                ];
            }
        }

        // SSS güvenlik ağı
        $sssSonuclari = $this->sssArama->ara($yorum['anahtar_kelimeler']);
        if ($sssSonuclari->isNotEmpty()) {
            return [
                'niyet' => 'sss',
                'baslik' => '💡 Sıkça Sorulan Sorular',
                'mesaj' => 'Soruyla eşleşen yardım maddeleri:',
                'sonuclar' => $sssSonuclari,
                'ilanBaglantisi' => null,
            ];
        }

        // 8. Hiçbiri eşleşmedi — Akıllı alternatif önerisi
        $hedefUlke = $this->dogrulaUlke($varsayilanUlkeKodu);
        $country = $hedefUlke ? Country::query()->where('code', $hedefUlke)->first() : null;
        $ulkeAdi = $country ? $country->name_tr : 'seçili ülkede';
        $ulkeEmoji = $country ? $country->emoji : '🌍';

        if ($yorum['niyet'] === 'rehber') {
            $hedefUlkeRehber = $this->dogrulaUlke($yorum['ulke_kodu']) ?? $hedefUlke;
            $countryRehber = $hedefUlkeRehber ? Country::query()->where('code', $hedefUlkeRehber)->first() : null;
            $ulkeAdiRehber = $countryRehber ? $countryRehber->name_tr : 'bulunduğunuz ülkede';
            $ulkeEmojiRehber = $countryRehber ? $countryRehber->emoji : '🏛️';

            return [
                'niyet' => 'belirsiz',
                'baslik' => "🏛️ {$ulkeEmojiRehber} T.C. {$ulkeAdiRehber} Konsolosluk Rehberi",
                'mesaj' => "{$ulkeAdiRehber} için aradığınız resmî işlem rehber kaydı henüz sisteme eklenmemiş olabilir. T.C. Dışişleri Bakanlığı e-Konsolosluk sistemi ve Çağrı Merkezi üzerinden randevu ve güncel evrak bilgilerine doğrudan ulaşabilirsiniz.",
                'oneri' => 'Resmî randevu almak için konsolosluk.gov.tr portalını ziyaret edebilir veya 7/24 çağrı merkezini arayabilirsiniz.',
                'eylemler' => [
                    ['baslik' => '🌐 e-Konsolosluk Randevu Portalı', 'url' => 'https://www.konsolosluk.gov.tr', 'stil' => 'primary', 'ikon' => 'globe'],
                    ['baslik' => '📞 Konsolosluk Hattı (+90 312 292 29 29)', 'url' => 'tel:+903122922929', 'stil' => 'secondary', 'ikon' => 'phone'],
                    ['baslik' => '📖 Genel Konsolosluk Rehberi', 'url' => url('/rehber'), 'stil' => 'outline', 'ikon' => 'building-library'],
                ],
                'ulke' => ['kod' => $hedefUlkeRehber, 'ad' => $ulkeAdiRehber, 'emoji' => $ulkeEmojiRehber],
                'sonuclar' => collect(),
                'ilanBaglantisi' => $this->ilanBaglantisi($sorgu),
            ];
        }

        return [
            'niyet' => 'belirsiz',
            'baslik' => '🔍 Rehber İçeriği Bulunamadı',
            'mesaj' => "{$ulkeAdi} için bu konuda henüz yayınlanmış resmî bir rehber kaydı bulunmuyor.",
            'oneri' => 'İlanlar pazarında arama yapabilir, e-Konsolosluk portalına gidebilir veya topluluğa sorabilirsiniz.',
            'eylemler' => [
                ['baslik' => '🌐 e-Konsolosluk Randevu Portalı', 'url' => 'https://www.konsolosluk.gov.tr', 'stil' => 'primary', 'ikon' => 'globe'],
                ['baslik' => '📢 Platform İlanlarında Ara', 'url' => $this->ilanBaglantisi($sorgu), 'stil' => 'outline', 'ikon' => 'magnifying-glass'],
            ],
            'ulke' => ['kod' => $hedefUlke, 'ad' => $ulkeAdi, 'emoji' => $ulkeEmoji],
            'sonuclar' => collect(),
            'ilanBaglantisi' => $this->ilanBaglantisi($sorgu),
        ];
    }

    /**
     * @return array{
     *     niyet: string,
     *     baslik: string,
     *     mesaj: string,
     *     oneri: string,
     *     eylemler: list<array{baslik: string, url: string, stil: string, ikon?: string}>,
     *     sonuclar: Collection<int, array{baslik: string, altbaslik: string, url: string}>,
     *     ilanBaglantisi: ?string
     * }
     */
    private function eylemSonucu(string $eylemTuru, ?string $varsayilanUlkeKodu): array
    {
        if ($eylemTuru === 'takim_kur') {
            return [
                'niyet' => 'eylem',
                'baslik' => '⚽ Futbol Takımı Kur',
                'mesaj' => 'Kendi halı saha takımınızı dakikalar içinde oluşturun, yapay zeka ile profesyonel logo üretin ve maçlara katılın!',
                'oneri' => 'Takımınızı kurduktan sonra maç daveti gönderebilir veya oyuncu arayan futbolculara ulaşabilirsiniz.',
                'eylemler' => [
                    ['baslik' => '⚽ Takımını Oluştur', 'url' => route('football.teams.create'), 'stil' => 'primary', 'ikon' => 'shield'],
                    ['baslik' => "🏆 Futbol Hub'ı", 'url' => route('football.index'), 'stil' => 'outline', 'ikon' => 'trophy'],
                ],
                'sonuclar' => collect(),
                'ilanBaglantisi' => route('football.index'),
            ];
        }

        if ($eylemTuru === 'acil') {
            return [
                'niyet' => 'eylem',
                'baslik' => '🚨 Acil Durum & Konsolosluk Hattı',
                'mesaj' => 'T.C. Dışişleri Bakanlığı Konsolosluk Çağrı Merkezi 7/24 kesintisiz hizmet vermektedir.',
                'oneri' => 'Acil pasaport, vefat, gözaltı veya kaza gibi durumlarda lütfen doğrudan çağrı merkezini arayınız.',
                'eylemler' => [
                    ['baslik' => '📞 +90 312 292 29 29 (Hemen Ara)', 'url' => 'tel:+903122922929', 'stil' => 'primary', 'ikon' => 'phone'],
                    ['baslik' => '🚨 Acil Durum Rehberi', 'url' => url('/acil'), 'stil' => 'outline', 'ikon' => 'exclamation-circle'],
                ],
                'sonuclar' => collect(),
                'ilanBaglantisi' => null,
            ];
        }

        // Varsayılan eylem: İlan ver
        return [
            'niyet' => 'eylem',
            'baslik' => '📢 Hemen İlan Ver',
            'mesaj' => 'Nisoya üzerinde ürün, hizmet, ev veya iş ilanı yayınlayarak diaspora topluluğuna hızlıca ulaşın.',
            'oneri' => 'İlan vermek ücretsiz ve onay süreci oldukça hızlıdır.',
            'eylemler' => [
                ['baslik' => '📝 Ücretsiz İlan Ver', 'url' => url('/ilan-ver'), 'stil' => 'primary', 'ikon' => 'plus'],
                ['baslik' => '💼 İş İlanı Ver', 'url' => route('panel.jobs.create'), 'stil' => 'secondary', 'ikon' => 'briefcase'],
            ],
            'sonuclar' => collect(),
            'ilanBaglantisi' => url('/ilan-ver'),
        ];
    }

    /**
     * @return array{niyet: string, sonuclar: Collection<int, array{baslik: string, altbaslik: string, url: string}>, ilanBaglantisi: ?string}
     */
    private function linkSonucu(string $niyet, string $baglanti): array
    {
        return ['niyet' => $niyet, 'sonuclar' => collect(), 'ilanBaglantisi' => $baglanti];
    }

    private function ilanBaglantisi(string $sorgu): string
    {
        return url('/ilanlar').'?q='.urlencode($sorgu);
    }

    private function isBaglantisi(string $sorgu, ?string $ulkeKodu): string
    {
        $params = ['q' => $sorgu];

        $ulkeKodu = $this->dogrulaUlke($ulkeKodu);
        if ($ulkeKodu !== null) {
            $params['ulke'] = $ulkeKodu;
        }

        return url('/isler').'?'.http_build_query($params);
    }

    private function dogrulaUlke(?string $kod): ?string
    {
        if ($kod === null || trim($kod) === '') {
            return null;
        }

        $kod = strtoupper(trim($kod));

        return Country::query()->where('is_active', true)->where('code', $kod)->exists() ? $kod : null;
    }

    /**
     * @return array{niyet: string, eylem_turu?: string, ulke_kodu: ?string, sehir: ?string, islem_turu_slug: ?string, yasam_kategori_slug: ?string, anahtar_kelimeler: list<string>}
     */
    private function yorumla(string $sorgu, ?string $varsayilanUlkeKodu = null): array
    {
        // 1. Adım: Hızlı Kuralcı Eşleştirici (0ms, 0 API maliyeti)
        $hizli = $this->hizliNiyetSorgula($sorgu, $varsayilanUlkeKodu);
        if ($hizli !== null) {
            return $hizli;
        }

        // 2. Adım: LLM / Claude ile Derin Yorumlama
        $anahtar = 'nisoya_ai_arama:'.md5(mb_strtolower(trim($sorgu)));

        $yorum = Cache::remember($anahtar, now()->addDays(self::ONBELLEK_SURESI_GUN), function () use ($sorgu) {
            try {
                $veri = $this->ai->analyzeText($this->istem($sorgu), $this->sema());
            } catch (\Throwable $e) {
                Log::warning('Nisoya AI arama yorumlama başarısız', ['exception' => $e->getMessage()]);

                return null;
            }

            return is_array($veri) ? $this->dogrula($veri) : null;
        });

        return $yorum ?? [
            'niyet' => 'belirsiz',
            'ulke_kodu' => null,
            'sehir' => null,
            'islem_turu_slug' => null,
            'yasam_kategori_slug' => null,
            'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
        ];
    }

    /**
     * Açık anahtar kelimeleri ve doğrudan eylemleri anında sınıflandırır.
     *
     * @return ?array{niyet: string, eylem_turu?: string, ulke_kodu: ?string, sehir: ?string, islem_turu_slug: ?string, yasam_kategori_slug: ?string, anahtar_kelimeler: list<string>}
     */
    private function hizliNiyetSorgula(string $sorgu, ?string $varsayilanUlkeKodu): ?array
    {
        $kucuk = mb_strtolower(trim($sorgu));

        // 1. Doğrudan Eylemler (Aksiyon Talepleri)
        if (preg_match('/\b(ilan\s*(ver|ekle|a[cç]|olu[sş]tur)[a-zçğıöşü]*|nas[iı]l\s*ilan)\b/ui', $kucuk)) {
            return [
                'niyet' => 'eylem',
                'eylem_turu' => 'ilan_ver',
                'ulke_kodu' => $varsayilanUlkeKodu,
                'sehir' => null,
                'islem_turu_slug' => null,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => ['ilan', 'ver'],
            ];
        }

        if (preg_match('/\b(tak[iı]m\s*(kur|olu[sş]tur|yeni)[a-zçğıöşü]*)\b/ui', $kucuk)) {
            return [
                'niyet' => 'eylem',
                'eylem_turu' => 'takim_kur',
                'ulke_kodu' => $varsayilanUlkeKodu,
                'sehir' => null,
                'islem_turu_slug' => null,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => ['takım', 'kur'],
            ];
        }

        if (preg_match('/\b(acil|ambulans|yard[iı]m\s*hatt[iı]|konsolosluk\s*telefon)\b/ui', $kucuk)) {
            return [
                'niyet' => 'eylem',
                'eylem_turu' => 'acil',
                'ulke_kodu' => $varsayilanUlkeKodu,
                'sehir' => null,
                'islem_turu_slug' => null,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => ['acil', 'çağrı'],
            ];
        }

        // 2. Spor & Futbol terimleri
        if (preg_match('/\b(ma[cç]|ma[cç]lar|futbol|hal[iı]\s*saha|tak[iı]m|tak[iı]mlar|krampon|kaleci|turnuva|lig|halisaha)\b/ui', $kucuk)) {
            $ulkeKodu = $this->metindenUlkeCikar($kucuk) ?? $varsayilanUlkeKodu;

            return [
                'niyet' => 'spor',
                'ulke_kodu' => $ulkeKodu,
                'sehir' => null,
                'islem_turu_slug' => null,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
            ];
        }

        // 3. İş & Kariyer doğrudan terimleri (ör. "iş", "işler", "iş ilanları", "garson", "şoför", "eleman")
        if (preg_match('/^(\s*i[sş]\s*|\s*i[sş]ler\s*|\s*i[sş]\s*ilan[a-zçğıöşü]*\s*|\s*eleman\s*|\s*garson\s*|\s*[sş]of[oö]r\s*|\s*kariyer\s*|\s*i[sş]\s*bul\s*)$/ui', $kucuk) ||
            preg_match('/\b(i[sş]\s*ilanlar[iı]|eleman\s*aran[iı]yor|garson\s*aran[iı]yor|[sş]of[oö]r\s*aran[iı]yor)\b/ui', $kucuk)) {
            $ulkeKodu = $this->metindenUlkeCikar($kucuk) ?? $varsayilanUlkeKodu;

            return [
                'niyet' => 'is',
                'ulke_kodu' => $ulkeKodu,
                'sehir' => null,
                'islem_turu_slug' => null,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
            ];
        }

        // 4. Esnaf, Pazar Yeri & Hizmetler (ör. "avukat", "doktor", "nakliyat", "tamirci", "kiralık")
        if (preg_match('/^(\s*avukat\s*|\s*doktor\s*|\s*hekim\s*|\s*nakliyat\s*|\s*nakliye\s*|\s*tamirci\s*|\s*kiral[iı]k\s*|\s*sat[iı]l[iı]k\s*|\s*ikinci\s*el\s*|\s*berber\s*|\s*kuaf[oö]r\s*|\s*terzi\s*|\s*muhasebeci\s*|\s*terc[uü]man\s*)$/ui', $kucuk) ||
            preg_match('/\b(t[uü]rk\s*avukat|t[uü]rk\s*doktor|evden\s*eve\s*nakliyat|kiral[iı]k\s*ev|kiral[iı]k\s*oda|ikinci\s*el\s*e[sş]ya)\b/ui', $kucuk)) {
            $ulkeKodu = $this->metindenUlkeCikar($kucuk) ?? $varsayilanUlkeKodu;

            return [
                'niyet' => 'ilan',
                'ulke_kodu' => $ulkeKodu,
                'sehir' => null,
                'islem_turu_slug' => null,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
            ];
        }

        // 5. Konsolosluk & Pasaport doğrudan terimleri (ör. "pasaport", "pasaportum", "askerlik", "noter")
        if (preg_match('/^(\s*pasaport\s*|\s*pasaportum\s*|\s*askerlik\s*|\s*tecil\s*|\s*apostil\s*|\s*noter\s*|\s*noterlik\s*|\s*konsolosluk\s*|\s*e-konsolosluk\s*)$/ui', $kucuk)) {
            $ulkeKodu = $this->metindenUlkeCikar($kucuk) ?? $varsayilanUlkeKodu;
            $slug = match (true) {
                str_contains($kucuk, 'pasaport') => 'pasaport',
                str_contains($kucuk, 'askerlik') || str_contains($kucuk, 'tecil') => 'askerlik',
                str_contains($kucuk, 'noter') => 'noterlik',
                str_contains($kucuk, 'apostil') => 'apostil',
                default => null,
            };

            return [
                'niyet' => 'rehber',
                'ulke_kodu' => $ulkeKodu,
                'sehir' => null,
                'islem_turu_slug' => $slug,
                'yasam_kategori_slug' => null,
                'anahtar_kelimeler' => $this->kabaKelimelerAyikla($sorgu),
            ];
        }

        return null;
    }

    private function metindenUlkeCikar(string $metin): ?string
    {
        $harita = [
            'kırgız' => 'KG', 'kirgiz' => 'KG', 'bişkek' => 'KG', 'biskek' => 'KG',
            'almanya' => 'DE', 'berlin' => 'DE', 'köln' => 'DE', 'koln' => 'DE', 'münih' => 'DE', 'frankfurt' => 'DE',
            'türkiye' => 'TR', 'turkiye' => 'TR', 'istanbul' => 'TR', 'ankara' => 'TR',
            'hollanda' => 'NL', 'amsterdam' => 'NL',
            'avusturya' => 'AT', 'viyana' => 'AT',
            'belçika' => 'BE', 'belcika' => 'BE', 'brüksel' => 'BE',
            'fransa' => 'FR', 'paris' => 'FR',
            'ingiltere' => 'GB', 'londra' => 'GB',
            'amerika' => 'US', 'abd' => 'US',
            'azerbaycan' => 'AZ', 'bakü' => 'AZ', 'baku' => 'AZ',
            'kazakistan' => 'KZ', 'almatı' => 'KZ', 'astana' => 'KZ',
            'özbekistan' => 'UZ', 'ozbekistan' => 'UZ', 'taşkent' => 'UZ',
        ];

        foreach ($harita as $kelime => $kod) {
            if (str_contains($metin, $kelime)) {
                return $kod;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $veri
     * @return array{niyet: string, eylem_turu?: string, ulke_kodu: ?string, sehir: ?string, islem_turu_slug: ?string, yasam_kategori_slug: ?string, anahtar_kelimeler: list<string>}
     */
    private function dogrula(array $veri): array
    {
        $niyet = $veri['niyet'] ?? null;
        if (! in_array($niyet, ['rehber', 'yasam', 'spor', 'is', 'ilan', 'eylem', 'sss', 'kapsam_disi', 'belirsiz'], true)) {
            $niyet = 'belirsiz';
        }

        $anahtarKelimeler = is_array($veri['anahtar_kelimeler'] ?? null)
            ? array_values(array_filter(
                array_map(fn ($k) => trim((string) $k), $veri['anahtar_kelimeler']),
                fn (string $k) => $k !== '',
            ))
            : [];

        return [
            'niyet' => $niyet,
            'ulke_kodu' => filled($veri['ulke_kodu'] ?? null) ? (string) $veri['ulke_kodu'] : null,
            'sehir' => filled($veri['sehir'] ?? null) ? (string) $veri['sehir'] : null,
            'islem_turu_slug' => filled($veri['islem_turu_slug'] ?? null) ? (string) $veri['islem_turu_slug'] : null,
            'yasam_kategori_slug' => filled($veri['yasam_kategori_slug'] ?? null) ? (string) $veri['yasam_kategori_slug'] : null,
            'anahtar_kelimeler' => $anahtarKelimeler,
        ];
    }

    /** AI tamamen başarısız olursa güvenlik ağı: kısa kelimeleri (ve/bir/mi) ele. */
    private function kabaKelimelerAyikla(string $sorgu): array
    {
        return array_values(array_filter(
            preg_split('/\s+/u', trim($sorgu)) ?: [],
            fn (string $kelime) => mb_strlen($kelime) >= 3,
        ));
    }

    private function istem(string $sorgu): string
    {
        $ulkeler = $this->rehberYuzeyi->kapsananUlkeler()
            ->map(fn ($u) => $u->code.' ('.$u->name_tr.')')
            ->implode(', ');

        $islemTurleri = IslemTuru::query()
            ->where('is_active', true)
            ->get(['ad', 'slug'])
            ->map(fn ($t) => $t->slug.' ('.$t->ad.')')
            ->implode(', ');

        $yasamKategorileri = YasamKategorisi::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['ad', 'slug'])
            ->map(fn ($k) => $k->slug.' ('.$k->ad.')')
            ->implode(', ');

        return implode("\n", [
            'Kullanıcı, yurt dışındaki Türklere yönelik "Nisoya" adlı sitenin anasayfasındaki',
            'yapay zeka arama çubuğuna bir soru yazdı. Görevin bu sorunun NİYETİNİ sınıflandırmak.',
            '',
            'NİYET SEÇENEKLERİ:',
            '- "rehber": T.C.\'nin KENDİ vatandaşına KENDİ konsolosluğunda sunduğu resmî işlem',
            '  (ör. pasaport, vekaletname, askerlik, apostil, vergi numarası, ehliyet gibi).',
            '- "kapsam_disi": ÜÇÜNCÜ BİR ÜLKEYE seyahat/göç için gereken vize/ikamet işlemi',
            '  (ör. "Tayland vizesi nasıl alınır", "Amerika\'ya turist vizesi", "Schengen vizesi")',
            '  — T.C. konsoloslukları bu hizmeti SUNMAZ, tamamen gidilecek ülkenin işidir.',
            '- "yasam": resmî bir işlem DEĞİL, gündelik yaşam pratiği (ör. "Almanya\'da kira nasıl',
            '  ödenir", "SSN\'siz banka hesabı açma", sağlık sigortası, entegrasyon).',
            '- "spor": futbol, halı saha, maç organizasyonu, futbol takımı kurma/bulma, kaleci/oyuncu arama, halı saha tesisleri (ör. "maç", "halı saha", "futbol takımı", "maç yapacak adam").',
            '- "is": bir İŞ/kariyer ilanı aranıyor (ör. "İstanbul\'da satış temsilcisi", "uzaktan yazılımcı iş", "iş arıyorum").',
            '- "eylem": doğrudan bir site eylemi talebi (ör. "ilan ver", "takım kur", "kayıt ol", "acil yardım").',
            '- "ilan": bir hizmet/ürün/usta/hoca aranıyor (ör. "Berlin\'de temizlikçi", "ikinci el araba").',
            '- "sss": Nisoya\'nın kendisiyle ilgili genel bir soru (ör. "Nisoya ücretli mi", "ödeme nasıl yapılıyor").',
            '- "belirsiz": hiçbirine net uymuyor.',
            '',
            'KURALLAR:',
            '- `ulke_kodu` YALNIZ aşağıdaki listeden bir kod olabilir; soruda ülke geçmiyorsa null bırak.',
            '- `sehir`: soruda geçen şehir adı (ör. Bişkek, Berlin, Köln) varsa doldur, yoksa null.',
            '- `islem_turu_slug` YALNIZ niyet "rehber" ise doldur; değilse null bırak.',
            '- `yasam_kategori_slug` YALNIZ niyet "yasam" ise doldur; değilse null bırak.',
            '- `anahtar_kelimeler`: sorunun özünü yakalayan kelimeler, HER ZAMAN doldur.',
            '',
            'HAZIR ÜLKELER: '.($ulkeler ?: '(şu an hiçbiri hazır değil)'),
            '',
            'İŞLEM TÜRLERİ (yalnız "rehber" niyeti için): '.($islemTurleri ?: '(şu an tanımlı değil)'),
            '',
            'YAŞAM REHBERİ KATEGORİLERİ (yalnız "yasam" niyeti için): '.($yasamKategorileri ?: '(şu an tanımlı değil)'),
            '',
            '--- SORU ---',
            Str::limit($sorgu, 200, ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'niyet' => [
                    'type' => 'string',
                    'enum' => ['rehber', 'yasam', 'spor', 'is', 'ilan', 'eylem', 'sss', 'kapsam_disi', 'belirsiz'],
                ],
                'ulke_kodu' => ['type' => ['string', 'null']],
                'sehir' => ['type' => ['string', 'null']],
                'islem_turu_slug' => ['type' => ['string', 'null']],
                'yasam_kategori_slug' => ['type' => ['string', 'null']],
                'anahtar_kelimeler' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['niyet', 'ulke_kodu', 'islem_turu_slug', 'yasam_kategori_slug', 'anahtar_kelimeler'],
            'additionalProperties' => false,
        ];
    }
}
