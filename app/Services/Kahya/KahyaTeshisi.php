<?php

namespace App\Services\Kahya;

use App\Enums\ListingStatus;
use App\Models\BekleyenHamle;
use App\Models\KahyaGorevi;
use App\Models\Listing;
use App\Models\User;

/**
 * Kâhya'nın tek teşhis çıktısı — günlük raporun, panelin ve (ileride)
 * MCP araçlarının ORTAK kaynağı.
 *
 * NEDEN TEK NOKTA: bu depoda aynı bilginin iki yerde ayrı hesaplanması
 * kanıtlanmış bir hata kaynağı. `BekleyenIslerWidget` ile günlük rapor ayrı
 * ayrı yazılsaydı, biri güncellenip diğeri unutulurdu — panelde görünen sayı
 * ile e-postadaki sayı farklı olurdu ve hangisinin doğru olduğu bilinemezdi.
 *
 * ---------------------------------------------------------------------------
 * "GERÇEK ENVANTER" SATIRI KALDIRILAMAZ
 *
 * `gercekEnvanter()` her raporun en üstünde durur ve pazaryerinin gerçek
 * doluluğunu söyler. 2026-07-29 ölçümü: sitede TOPLAM 3 ilan var ve üçü de
 * sahibin kendisine ait — yani üçüncü taraf envanteri sıfır. Bütün büyüme
 * çalışmasının gerçek darboğazı bu ve bir gösterge panelinin en kolay
 * gizlediği şey de bu.
 *
 * Bu yüzden sayı iki parça hâlinde verilir: toplam ilan VE benzersiz satıcı.
 * Tek başına "12 ilan" iyi görünür; "12 ilan, 1 satıcı" gerçeği söyler.
 * ---------------------------------------------------------------------------
 */
class KahyaTeshisi
{
    public function __construct(
        private readonly BekleyenIsler $bekleyen,
        private readonly MedyaDogrulayici $medya,
        private readonly EksikAlanTarayici $eksik,
        private readonly LogOzeti $log,
    ) {}

    /**
     * MALİYET: ~26 SQL sorgusu + 2000'e kadar `Storage::exists()` çağrısı +
     * storage/logs altındaki TÜM .log dosyalarının baştan sona okunması.
     * GÜNDE BİR KEZ çalışmak üzere tasarlandı; etkileşimli çağrılarda (panel,
     * MCP) parametreleri küçültmek gerekebilir.
     *
     * @param  ?int  $medyaLimit  null ise `config('kahya.medya_tarama_limiti')`
     * @param  ?int  $logSaat  null ise `config('kahya.log_penceresi_saat')`
     * @return array{
     *   uretildi: string,
     *   envanter: array{ilan: int, satici: int, uyari: ?string},
     *   bekleyen: list<array{anahtar: string, etiket: string, adet: int, aciliyet: string, aciklama: string}>,
     *   bozuk: array{medya: array<string, mixed>, log: array<string, mixed>},
     *   eksik: array<string, mixed>
     * }
     */
    public function topla(?int $medyaLimit = null, ?int $logSaat = null): array
    {
        return [
            'uretildi' => now()->toAtomString(),
            'envanter' => $this->gercekEnvanter(),
            'bekleyen' => $this->bekleyen->topla(),
            'bozuk' => [
                'medya' => $this->medya->dogrula($medyaLimit),
                'log' => $this->log->ozetle($logSaat),
            ],
            'eksik' => $this->eksik->tara(),
            'gorevler' => $this->gorevDurumu(),
        ];
    }

    /**
     * Görev defterinin rapor özeti (F2): açık misyonlar + karar bekleyen
     * hamle kartları. Günlük raporun varlık sebebiyle aynı gerekçe —
     * zamanlanmış işler gibi uzun misyonlar da SESSİZCE ölür; her sabah
     * raporda görünen görev, unutulamayan görevdir.
     *
     * @return array{acik: list<array<string, mixed>>, bekleyen_hamle: int}
     */
    public function gorevDurumu(): array
    {
        $acik = KahyaGorevi::query()
            ->acik()
            ->latest('son_islem_at')
            ->limit(10)
            ->get()
            ->map(function (KahyaGorevi $g): array {
                $ilerleme = $g->ilerleme();

                return [
                    'id' => $g->id,
                    'baslik' => $g->baslik,
                    'yapildi' => $ilerleme['yapildi'],
                    'toplam' => $ilerleme['toplam'],
                    'siradaki' => $g->siradakiAdim(),
                    // Hareketsiz görev uyarısının hammaddesi.
                    'hareketsiz_gun' => $g->son_islem_at !== null
                        ? (int) $g->son_islem_at->diffInDays(now())
                        : null,
                ];
            })
            ->all();

        return [
            'acik' => $acik,
            'bekleyen_hamle' => BekleyenHamle::query()->beklemede()->count(),
        ];
    }

    /**
     * Pazaryerinin GERÇEK doluluğu.
     *
     * DEMO KAYITLAR HER ZAMAN DIŞLANIR — bu bir seçenek değil.
     *
     * Bu satırın var olma sebebi sahibin kendi kendini kandırmasını
     * engellemek. Örnek veri makinesi (Faz A/B) sayıyı istendiği kadar
     * şişirebilir; o sayı buraya karışsaydı Kâhya'nın en değerli ölçüsü
     * bozulur ve "pazaryeri dolu görünüyor" yanılsaması tam olarak ölçmek
     * için yazılmış araç tarafından üretilirdi.
     *
     * @return array{ilan: int, satici: int, uyari: ?string}
     */
    public function gercekEnvanter(): array
    {
        $ilan = Listing::query()->where('status', ListingStatus::Aktif)->where('is_demo', false)->count();

        $satici = Listing::query()
            ->where('status', ListingStatus::Aktif)
            ->where('is_demo', false)
            ->distinct()
            ->count('user_id');

        // Tek satıcı = pazaryeri değil, vitrin. Bu uyarı bilinçli olarak
        // sert: rapor okunurken gözden kaçmasın diye.
        $uyari = match (true) {
            $ilan === 0 => 'Pazaryeri boş — hiç aktif ilan yok.',
            $satici <= 1 => 'Tüm ilanlar tek kişiye ait — üçüncü taraf envanteri yok.',
            default => null,
        };

        return ['ilan' => $ilan, 'satici' => $satici, 'uyari' => $uyari];
    }

    /**
     * Son 24 saatte ne oldu — raporun "ne oldu" bölümü.
     *
     * @return array<string, int>
     */
    public function sonYirmiDortSaat(): array
    {
        $esik = now()->subDay();

        // Demo kayıtlar burada da sayılmaz: "son 24 saatte 8 yeni ilan geldi"
        // cümlesi, sekizini de kendin ürettiysen bilgi değil gürültüdür.
        return [
            'yeni_uye' => User::query()->where('created_at', '>=', $esik)->where('is_demo', false)->count(),
            'yeni_ilan' => Listing::query()->where('created_at', '>=', $esik)->where('is_demo', false)->count(),
        ];
    }
}
