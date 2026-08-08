<?php

namespace App\Services\Medya;

use App\Models\MediaAsset;
use App\Models\MediaRendition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Medya boru hattının GİRİŞ KAPISI: yüklenen dosyayı ana kopya olarak saklar
 * ve istenen slot türevini üretir.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md
 *
 * ---------------------------------------------------------------------------
 * ANA KOPYA `local` DİSKE YAZILIR, `public`'e DEĞİL
 *
 * Tasarımın temel kuralı: sitede görünen hiçbir şey ana kopya değildir. Ana
 * kopya `public` diskte dursaydı adresi tahmin edilebilir olurdu ve
 * 4469×2979'luk ham dosya, hiçbir yerde gösterilmediği hâlde indirilebilirdi.
 * `local` disk web sunucusuna açık değil.
 *
 * (Aynı gerekçe Medya Kütüphanesi'nde de yazılı: "public diske ASLA yazılmaz".)
 */
class MedyaDeposu
{
    public function __construct(private readonly MedyaTuretici $turetici) {}

    /**
     * Yüklenen dosyayı ana kopya yapar ve slot türevini üretir.
     *
     * AYNI DOSYA İKİ KEZ YÜKLENİRSE tek ana kopya olur: içerik özeti (sha256)
     * benzersiz. Bugün kütüphanede aynı görselin kopyaları birikiyordu.
     */
    public function al(UploadedFile $dosya, string $slot, ?int $yukleyenId = null): MediaRendition
    {
        $asset = $this->anaKopyaOlustur($dosya, $yukleyenId);

        return $this->turetici->turet($asset, $slot);
    }

    /** Ana kopyayı oluşturur ya da aynısı varsa onu döndürür. */
    public function anaKopyaOlustur(UploadedFile $dosya, ?int $yukleyenId = null): MediaAsset
    {
        $gercekYol = $dosya->getRealPath();

        if ($gercekYol === false || ! is_file($gercekYol)) {
            throw new RuntimeException('Yüklenen dosya okunamadı.');
        }

        $ozet = hash_file('sha256', $gercekYol);

        // TEKİLLEŞTİRME — aynı içerik ikinci kez ana kopya olmaz.
        $mevcut = MediaAsset::query()->where('ozet', $ozet)->first();
        if ($mevcut) {
            return $mevcut;
        }

        $boyutlar = @getimagesize($gercekYol);

        $yol = 'medya-ana/'.Str::uuid()->toString().'.'.($dosya->guessExtension() ?: 'bin');

        // Dönüş KONTROL EDİLİR: disk dolduğunda put() sessizce false döner ve
        // DB hiç yazılmamış bir dosyayı gösterir (ImageService'teki aynı ders).
        if (! Storage::disk('local')->put($yol, file_get_contents($gercekYol))) {
            throw new RuntimeException('Ana kopya diske yazılamadı.');
        }

        return MediaAsset::query()->create([
            'yol' => $yol,
            'ad' => $dosya->getClientOriginalName(),
            'mime' => $dosya->getMimeType(),
            'en' => $boyutlar[0] ?? null,
            'boy' => $boyutlar[1] ?? null,
            'bayt' => $dosya->getSize(),
            'ozet' => $ozet,
            'yukleyen_id' => $yukleyenId,
        ]);
    }

    /**
     * Odak noktasını değiştirir ve ETKİLENEN TÜM TÜREVLERİ yeniden üretir.
     *
     * Odak yalnız kayıtta değişip türevler eski kalsaydı, panelde sürüklenen
     * nokta hiçbir şey yapmıyormuş gibi görünürdü — bu depoda tekrar eden
     * "ekran var, kablo yok" hatası.
     */
    public function odagiGuncelle(MediaAsset $asset, int $x, int $y): void
    {
        $asset->update([
            'odak_x' => max(0, min(100, $x)),
            'odak_y' => max(0, min(100, $y)),
        ]);

        foreach ($asset->renditions()->pluck('slot') as $slot) {
            $this->turetici->turet($asset->fresh(), $slot);
        }
    }
}
