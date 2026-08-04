<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * H1'i TANIMLAYICI yapar, sloganı bir basamak aşağı indirir (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * NEDEN
 *
 * Mobil ilk izlenim araştırmasının bulgusu: H1 ürünün NE OLDUĞUNU söylemiyordu.
 * "Tarif etmeye çalışma. / Türkçe anlat, iş bitsin." iyi bir cümle ama bir
 * DUYGU cümlesi; tanım 250px'teki paragrafta bekliyordu.
 *
 * Ölçüm: ziyaretçi kelimelerin en fazla %28'ini okuyor, göz izlemede deneklerin
 * %79'u tarıyor ve yalnız %16'sı kelime kelime okuyor. Mobilde baskın desen
 * "layer-cake": başlıklar okunur, gövde atlanır. Yani sitenin ne olduğunu
 * anlatan tek cümle, OKUNMAMA OLASILIĞI EN YÜKSEK biçimde yazılmıştı.
 *
 * NN/g'nin kuralı: "yeni ya da ünlü değilseniz" sayfaya sitenin ne yaptığını
 * özetleyen bir tagline ile başlayın.
 *
 * ---------------------------------------------------------------------------
 * SLOGAN SİLİNMİYOR
 *
 * `hero_aciklama` artık kapsam listesi değil SLOGAN taşıyor ve şablonda
 * H1'in altında daha küçük puntoyla basılıyor. Kapsam bilgisi ise okunmayan
 * bir paragraf yerine TARANABİLİR çiplere dönüştü (hero.blade.php).
 *
 * ---------------------------------------------------------------------------
 * KORUMALI — depodaki 2026_07_28 migration'ının aynı deseni
 *
 * Her değer ancak BUGÜNKÜ değeri bilinen eski metne birebir eşitse güncellenir.
 * Sahip aradan sonra kendi metnini yazdıysa DOKUNULMAZ. Bir migration
 * kullanıcının yazdığı içeriği asla ezmemeli.
 */
return new class extends Migration
{
    private const DEGISIKLIKLER = [
        'home.hero_satir1' => [
            'eski' => 'Tarif etmeye çalışma.',
            'yeni' => 'Şehrindeki Türkçe konuşan',
        ],
        'home.hero_vurgu' => [
            'eski' => 'Türkçe anlat, iş bitsin.',
            'yeni' => 'ustayı, hocayı, nakliyeciyi bul.',
        ],
        // Paragraf artık kapsam listesi DEĞİL: kapsam çiplere taşındı.
        // Burada slogan yaşıyor ve şablonda küçük puntoyla basılıyor.
        'home.hero_aciklama' => [
            'eski' => 'Nakliyeci, hoca, tamirci, kuaför, tercüman — şehrindeki Türkçe konuşan kişiyi bul, aracısız yaz, işini gör.',
            'yeni' => 'Tarif etmeye çalışma. Türkçe anlat, iş bitsin.',
        ],
    ];

    public function up(): void
    {
        $yazilacak = [];

        foreach (self::DEGISIKLIKLER as $anahtar => $d) {
            if (Settings::get($anahtar) === $d['eski']) {
                $yazilacak[$anahtar] = $d['yeni'];
            }
        }

        if ($yazilacak !== []) {
            Settings::setMany($yazilacak);
        }
    }

    public function down(): void
    {
        $yazilacak = [];

        foreach (self::DEGISIKLIKLER as $anahtar => $d) {
            if (Settings::get($anahtar) === $d['yeni']) {
                $yazilacak[$anahtar] = $d['eski'];
            }
        }

        if ($yazilacak !== []) {
            Settings::setMany($yazilacak);
        }
    }
};
