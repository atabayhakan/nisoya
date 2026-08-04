<?php

namespace App\Filament\Concerns;

use App\Filament\Pages\ElKitabi;
use App\Services\Rehber\ElKitabiRehberi;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

/**
 * Ekranın kendi rehber sayfasını açan "Yardım" başlık eylemi.
 *
 * ---------------------------------------------------------------------------
 * NEDEN SLIDE-OVER, NEDEN AYRI SAYFA DEĞİL
 *
 * Kullanıcı bir işi yaparken takılıyor. Onu El Kitabı'na göndermek işini
 * bıraktırır; geri döndüğünde formu baştan doldurur. Slide-over ekrandan
 * çıkarmaz — okur, kapatır, kaldığı yerden devam eder.
 *
 * ---------------------------------------------------------------------------
 * BAĞ SINIF ADINDAN KURULUR
 *
 * Markdown front-matter'ındaki `ekran:` alanı bu sınıfın adını taşır. Elle
 * yazılmış bir eşleme tablosu olsaydı sınıf yeniden adlandırıldığında bağ
 * sessizce kopardı; burada kopmaz, `RehberYardimiTest` yakalar.
 *
 * Rehber sayfası YOKSA düğme HİÇ GÖRÜNMEZ — boş bir yardım penceresi,
 * yardım olmamasından kötüdür.
 */
trait RehberYardimi
{
    protected static function rehberYardimAksiyonu(): ?Action
    {
        $sayfa = app(ElKitabiRehberi::class)->ekranIcin(static::class);

        if ($sayfa === null) {
            return null;
        }

        return Action::make('rehberYardim')
            ->label('Yardım')
            ->icon('heroicon-o-question-mark-circle')
            ->color('gray')
            ->slideOver()
            ->modalHeading($sayfa->baslik)
            ->modalDescription($sayfa->ozet ?: null)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Kapat')
            ->modalFooterActions([
                Action::make('elKitabiniAc')
                    ->label('El Kitabı\'nda aç')
                    ->icon('heroicon-o-book-open')
                    ->color('gray')
                    ->url(ElKitabi::getUrl()),
            ])
            ->modalContent(new HtmlString(
                '<div class="prose prose-sm dark:prose-invert max-w-none">'.$sayfa->html().'</div>'
            ));
    }
}
