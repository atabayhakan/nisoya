<?php

namespace App\Mcp\Araclar;

use App\Services\Kahya\EksikAlanTarayici;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * "Siteyi tarayıp doldurulması gereken yerleri söyle" isteğinin karşılığı.
 *
 * YALNIZ ANAHTAR ADI VE NEDENİ DÖNER, DEĞER DÖNMEZ. Bu bir tercih değil
 * zorunluluk: `site_settings` tablosunda `mail.password`, `ai.api_anahtari`
 * ve `growth.google_places_api_key` gibi sırlar duruyor. Bir aracın ayar
 * DEĞERİ okuması, ilk yanlış filtrede sırrı yapay zekâya taşımak demektir.
 * Burada okunan tek şey "dolu mu, boş mu".
 *
 * İsteğe bağlı alanlar tek tek listelenmez, yalnız sayılır — çoğunun boş
 * olması normaldir ve listelenmesi asıl sinyali boğar.
 */
#[Name('kahya-eksik-alanlar')]
#[Title('Doldurulmayı bekleyen alanlar')]
#[Description(
    'Sitenin doldurulmamış ayarlarını ve boş kategorilerini listeler: hangi KRİTİK ayar boş ve '.
    'boş olması neye mal oluyor, kaç isteğe bağlı alan boş, kaç kategoride hiç ilan yok. '.
    'GÜVENLİK: yalnız anahtar ADI ve gerekçesi döner — ayar DEĞERLERİ hiçbir koşulda dönmez, '.
    'çünkü aynı tabloda SMTP parolası ve API anahtarları duruyor.'
)]
#[IsReadOnly]
class EksikAlanlar extends KahyaAraci
{
    public function __construct(private readonly EksikAlanTarayici $tarayici) {}

    /** @return array<string, mixed> */
    protected function topla(Request $request): array
    {
        return $this->tarayici->tara();
    }
}
