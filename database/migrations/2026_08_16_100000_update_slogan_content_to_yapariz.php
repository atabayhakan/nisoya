<?php

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * Slogan "Ne İş Olursa Yaparım" → "Ne İş Olursa Yaparız" (2026-08-16, sahibin
 * kararı). Kaynak dosyalardaki (Blade/Notification/config default) metin
 * değişikliği YETMEZ: `SiteSetting`/`Page` içeriği `firstOrCreate` ile
 * seedleniyor, yani eski metin DB'ye bir kez yazıldıktan sonra kod
 * değişikliği görünmez kalır (bkz. konuşma özeti).
 *
 * SADECE hâlâ tam olarak eski metni taşıyan satırları düzeltir — admin
 * panelden farklı bir şeye çevrilmiş satırlara DOKUNMAZ. Ölçülen örnek:
 * canlıda `genel.slogan` zaten "Yurt Dışı Türklerin İş Arama Platformu",
 * `seo.default_title` zaten "Yaparız" — ikisi de bu koşulla eşleşmiyor,
 * yani bu migration onları OLDUĞU GİBİ bırakır.
 */
return new class extends Migration
{
    private const ESKI_HAKKIMIZDA = '"Ne İş Olursa Yaparım"';

    private const YENI_HAKKIMIZDA = '"Ne İş Olursa Yaparız"';

    public function up(): void
    {
        $this->degistir(
            'genel.slogan',
            'Ne İş Olursa Yaparım',
            'Ne İş Olursa Yaparız',
        );

        $this->degistir(
            'footer.aciklama',
            'Ne İş Olursa Yaparım. Yurt dışındaki Türklerin kendi aralarında yetenek ve hizmet pazaryeri.',
            'Ne İş Olursa Yaparız. Yurt dışındaki Türklerin kendi aralarında yetenek ve hizmet pazaryeri.',
        );

        $this->degistir(
            'seo.default_title',
            'Nisoya — Ne İş Olursa Yaparım',
            'Nisoya — Ne İş Olursa Yaparız',
        );

        $this->hakkimizdaSayfasi(self::ESKI_HAKKIMIZDA, self::YENI_HAKKIMIZDA);

        Settings::forget();
    }

    public function down(): void
    {
        $this->degistir(
            'genel.slogan',
            'Ne İş Olursa Yaparız',
            'Ne İş Olursa Yaparım',
        );

        $this->degistir(
            'footer.aciklama',
            'Ne İş Olursa Yaparız. Yurt dışındaki Türklerin kendi aralarında yetenek ve hizmet pazaryeri.',
            'Ne İş Olursa Yaparım. Yurt dışındaki Türklerin kendi aralarında yetenek ve hizmet pazaryeri.',
        );

        $this->degistir(
            'seo.default_title',
            'Nisoya — Ne İş Olursa Yaparız',
            'Nisoya — Ne İş Olursa Yaparım',
        );

        $this->hakkimizdaSayfasi(self::YENI_HAKKIMIZDA, self::ESKI_HAKKIMIZDA);

        Settings::forget();
    }

    private function degistir(string $anahtar, string $eski, string $yeni): void
    {
        SiteSetting::query()
            ->where('key', $anahtar)
            ->where('value', $eski)
            ->update(['value' => $yeni]);
    }

    private function hakkimizdaSayfasi(string $eski, string $yeni): void
    {
        $sayfa = Page::query()->where('slug', 'hakkimizda')->first();

        if ($sayfa === null) {
            return;
        }

        $bloklar = $sayfa->blocks;
        $degisti = false;

        foreach ($bloklar as $i => $blok) {
            $icerik = (string) ($blok['data']['content'] ?? '');

            if (($blok['type'] ?? null) === 'metin' && str_contains($icerik, $eski)) {
                $bloklar[$i]['data']['content'] = str_replace($eski, $yeni, $icerik);
                $degisti = true;
            }
        }

        if ($degisti) {
            $sayfa->update(['blocks' => $bloklar]);
        }
    }
};
