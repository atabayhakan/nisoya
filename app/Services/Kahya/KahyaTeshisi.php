<?php

namespace App\Services\Kahya;

use App\Enums\ListingStatus;
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
        ];
    }

    /**
     * Pazaryerinin GERÇEK doluluğu.
     *
     * @return array{ilan: int, satici: int, uyari: ?string}
     */
    public function gercekEnvanter(): array
    {
        $ilan = Listing::query()->where('status', ListingStatus::Aktif)->count();

        $satici = Listing::query()
            ->where('status', ListingStatus::Aktif)
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

        return [
            'yeni_uye' => User::query()->where('created_at', '>=', $esik)->count(),
            'yeni_ilan' => Listing::query()->where('created_at', '>=', $esik)->count(),
        ];
    }
}
