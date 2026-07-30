<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Enums\HafizaTuru;
use App\Models\KahyaHafizasi;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Sahibin "şunu hatırla" dediğini kalıcı hafızaya yazar (F1 — tasarım §2.3).
 *
 * Eylem olarak yaşıyor (ayrı bir servis değil) çünkü hafızaya yazmak da bir
 * YAZMA işlemidir ve bu evde yazmanın tek kuralı var: denetim izi bırakmayan
 * yapılamaz. Eylem olunca defter kaydı + geri alma bedava geliyor.
 */
class Hatirla extends Eylem
{
    public function ad(): string
    {
        return 'hatirla';
    }

    public function baslik(): string
    {
        return 'Hafızaya yaz';
    }

    public function aciklama(): string
    {
        return 'Sahibin verdiği bilgiyi/kuralı KALICI hafızaya yazar; sonraki her sohbette '
            .'"Hatırladıkların" bölümünde görürsün. Sahip "şunu hatırla", "unutma", "bundan '
            .'sonra hep böyle yap" gibi bir şey dediğinde kullan. Sohbet geçmişi 12 mesajda '
            .'unutur — kalıcı olması gerekenin yeri burası. Aynı bilgi zaten hafızadaysa '
            .'(Hatırladıkların bölümüne bak) TEKRAR yazma.';
    }

    public function sema(): array
    {
        return [
            'metin' => 'Hatırlanacak bilgi — kısa, kendi başına anlaşılır bir cümle. '
                .'Sahibin sözünü olduğu gibi kopyalama; kalıcı okunacak biçimde damıt.',
            'tur' => 'kural (davranış talimatı: "hep şöyle yap") · gercek (site/iş bilgisi) '
                .'· ders (bir hatadan çıkan sonuç) · not (gerisi). Emin değilsen not.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'metin' => ['required', 'string', 'min:10', 'max:500'],
            'tur' => ['nullable', 'string', 'in:kural,gercek,ders,not'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Tek satır, dışarıya hiçbir şey gitmiyor, geri alması tek tık.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $tur = HafizaTuru::from($p['tur'] ?? 'not');

        return "Hafızaya {$tur->etiket()} olarak yazılacak: \"{$p['metin']}\"";
    }

    public function uygula(array $p): array
    {
        $kayit = KahyaHafizasi::create([
            'tur' => HafizaTuru::from($p['tur'] ?? 'not'),
            'metin' => (string) $p['metin'],
            'kaynak' => KahyaHafizasi::KAYNAK_SAHIP,
        ]);

        return [
            'sonuc' => "Hafızaya yazıldı ({$kayit->tur->etiket()}): \"{$kayit->metin}\"",
            'geri_alma' => ['id' => $kayit->id],
        ];
    }

    public function geriAl(array $iz): string
    {
        $kayit = KahyaHafizasi::query()->find($iz['id'] ?? 0);

        if ($kayit === null) {
            return 'Kayıt zaten yok.';
        }

        $metin = $kayit->metin;
        $kayit->delete();

        return "Hafızadan silindi: \"{$metin}\"";
    }

    public function ornekler(): array
    {
        return [
            'şunu hatırla: kategori eklerken hep ikon da koy',
            'bundan sonra SEO metinlerini yayınlamadan önce bana göster',
            'not al: hedef kitlemiz önce Avrupa, Körfez ikinci öncelik',
        ];
    }
}
