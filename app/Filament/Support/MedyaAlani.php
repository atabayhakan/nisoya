<?php

namespace App\Filament\Support;

use App\Services\Medya\MedyaDeposu;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Boru hattına bağlı görsel yükleme alanı.
 *
 * Bkz. docs/plans/2026-08-09-medya-boru-hatti-design.md § C adımı
 *
 * ---------------------------------------------------------------------------
 * NEDEN TEK YARDIMCI
 *
 * Panelde beş ayrı yükleme noktası vardı ve her biri kendi kuralını kendi
 * biliyordu — daha doğrusu BİLMİYORDU: hepsi ham dosyayı `disk('public')`e
 * yazıyordu. Boyut/ağırlık yalnızca yardım metninde yazılıydı, kod okumuyordu.
 * Hero'da bunun bedeli ölçüldü: 4469×2979 / 402 KB dosya itirazsız geçti.
 *
 * Her sayfanın kaydetme akışına ayrı ayrı girmek yerine Filament'in KENDİ
 * yükleme kancası kullanılıyor (`saveUploadedFileUsing`). Böylece dosya daha
 * depolanırken boru hattından geçer ve forma dönen değer zaten TÜREVİN yolu
 * olur — sayfanın kaydetme kodunda tek satır değişmez.
 *
 * KULLANIM:
 *   MedyaAlani::make('og_image', 'seo_og')->label('Paylaşım görseli')
 */
class MedyaAlani
{
    /**
     * @param  string  $ad  form alan adı
     * @param  string  $slot  config/media_slots.php anahtarı
     */
    public static function make(string $ad, string $slot): FileUpload
    {
        $spec = (array) config("media_slots.{$slot}", []);

        return FileUpload::make($ad)
            ->image()
            ->disk('public')
            ->imageEditor()
            /*
             * `maxSize` boru hattının ÖNÜNDE durur ve bilerek CÖMERT:
             * amaç büyük dosyayı reddetmek değil, küçültmek. Yalnız absürt
             * boyutlar (bellek riski) kesilir.
             */
            ->maxSize(12288)
            ->helperText(self::yardimMetni($spec))
            ->saveUploadedFileUsing(static function (BaseFileUpload $alan, TemporaryUploadedFile $dosya) use ($slot): ?string {
                try {
                    return app(MedyaDeposu::class)->al($dosya, $slot, auth()->id())->yol;
                } catch (Throwable $e) {
                    /*
                     * BORU HATTI PATLARSA ESKİ DAVRANIŞA DÜŞ.
                     *
                     * Yükleme, görsel işlemenin bir ayrıntısı yüzünden tamamen
                     * başarısız olmamalı — sahip dosyasını kaybetmiş gibi
                     * hisseder. Ham dosya kaydedilir (eskiden hep böyleydi) ve
                     * hata kayda geçer; `media:yeniden-turet` sonradan
                     * toparlayabilir.
                     */
                    report($e);

                    return $alan->saveUploadedFile($dosya);
                }
            });
    }

    /** Slot ölçülerini insana okunur biçimde anlatır. */
    private static function yardimMetni(array $spec): string
    {
        $en = $spec['en'] ?? null;
        $boy = $spec['boy'] ?? null;

        if (! $en) {
            return 'Yüklenen görsel otomatik optimize edilir.';
        }

        $olcu = $boy ? "{$en}×{$boy}" : "{$en}px genişlik";

        // "Önerilir" DEMİYOR — artık gerçekten uygulanıyor. Eski metin
        // ("2400×1200 önerilir") kodun yapmadığı bir şeyi anlatıyordu.
        return $boy
            ? "Otomatik {$olcu} boyutuna getirilir ve WebP'ye çevrilir. Daha büyük yükleyebilirsin; küçük yüklersen büyütülmez."
            : "Otomatik {$olcu} sınırına indirilir ve WebP'ye çevrilir.";
    }
}
