<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\KahyaGorevi;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Bir görevde ilerleme işler: adım işaretle, not düş, durumu değiştir (F2).
 *
 * Tek eylemde üçü birden — model "3. adımı bitirdim, şu notu düş" gibi
 * bileşik ilerlemeleri tek çağrıda işleyebilsin. Hepsi isteğe bağlı ama
 * en az biri verilmeli; "hiçbir şey değiştirmeyen güncelleme" doğrulamada
 * reddedilir.
 */
class GorevGuncelle extends Eylem
{
    public function ad(): string
    {
        return 'gorev-guncelle';
    }

    public function baslik(): string
    {
        return 'Görev güncelle';
    }

    public function aciklama(): string
    {
        return 'Görev defterindeki bir görevde ilerleme işler: bir adımı yapıldı/atlandı '
            .'işaretler, ilerleme notu düşer ve/veya görevi kapatır (tamamlandi/iptal). '
            .'Bir görev üstünde çalıştığın HER turda ilerlemeyi buraya işle — deftere '
            .'işlenmeyen ilerleme, sahibin gözünde hiç olmamış ilerlemedir. Görev id\'si '
            .'"Görevlerin" bölümünde yazar.';
    }

    public function sema(): array
    {
        return [
            'id' => 'Görevin sayısal kimliği.',
            'adim_no' => 'İsteğe bağlı: işaretlenecek adımın sırası (1\'den başlar, "Görevlerin" bölümündeki sıra).',
            'adim_durum' => 'adim_no verildiyse: yapildi, atlandi ya da bekliyor.',
            'not' => 'İsteğe bağlı ilerleme notu — ne yaptın / ne öğrendin, tek cümle.',
            'durum' => 'İsteğe bağlı: görevi kapatmak için tamamlandi ya da iptal; tekrar açmak için acik.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:kahya_gorevleri,id'],
            'adim_no' => ['nullable', 'integer', 'min:1'],
            'adim_durum' => ['nullable', 'string', 'in:yapildi,atlandi,bekliyor', 'required_with:adim_no'],
            'not' => ['nullable', 'string', 'max:500'],
            'durum' => ['nullable', 'string', 'in:acik,tamamlandi,iptal'],
        ];
    }

    public function risk(): EylemRiski
    {
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $gorev = KahyaGorevi::query()->findOrFail($p['id']);
        $parcalar = [];

        if (! empty($p['adim_no'])) {
            $parcalar[] = "{$p['adim_no']}. adım → {$p['adim_durum']}";
        }
        if (! empty($p['not'])) {
            $parcalar[] = "not: \"{$p['not']}\"";
        }
        if (! empty($p['durum'])) {
            $parcalar[] = "durum → {$p['durum']}";
        }

        return "\"{$gorev->baslik}\" güncellenecek: ".implode(' · ', $parcalar);
    }

    public function uygula(array $p): array
    {
        $gorev = KahyaGorevi::query()->findOrFail($p['id']);

        if (empty($p['adim_no']) && empty($p['not']) && empty($p['durum'])) {
            throw new \RuntimeException('Güncellenecek bir şey verilmedi (adım, not ya da durum).');
        }

        // Geri alma izi = değişenden ÖNCEKİ hâl. Notlar dizisi büyüyerek
        // değişir; iz eski uzunluğu taşır, geri alma fazlasını kırpar.
        $iz = [
            'id' => $gorev->id,
            'durum' => $gorev->durum,
            'adimlar' => $gorev->adimlar,
            'not_sayisi' => count($gorev->ilerleme_notlari ?? []),
        ];

        $degisen = [];

        if (! empty($p['adim_no'])) {
            $adimlar = $gorev->adimlar ?? [];
            $indeks = (int) $p['adim_no'] - 1;

            if (! isset($adimlar[$indeks])) {
                throw new \RuntimeException("Görevde {$p['adim_no']}. adım yok (toplam ".count($adimlar).' adım).');
            }

            $adimlar[$indeks]['durum'] = (string) $p['adim_durum'];
            $gorev->adimlar = $adimlar;
            $degisen[] = "{$p['adim_no']}. adım {$p['adim_durum']}";
        }

        if (! empty($p['not'])) {
            $gorev->ilerleme_notlari = [
                ...($gorev->ilerleme_notlari ?? []),
                ['t' => now()->format('Y-m-d H:i'), 'not' => (string) $p['not']],
            ];
            $degisen[] = 'not düşüldü';
        }

        if (! empty($p['durum'])) {
            $gorev->durum = (string) $p['durum'];
            $degisen[] = "durum: {$p['durum']}";
        }

        $gorev->son_islem_at = now();
        $gorev->save();

        return [
            'sonuc' => "\"{$gorev->baslik}\" güncellendi (".implode(', ', $degisen).').',
            'geri_alma' => $iz,
        ];
    }

    public function geriAl(array $iz): string
    {
        $gorev = KahyaGorevi::query()->find($iz['id'] ?? 0);

        if ($gorev === null) {
            return 'Görev bu arada silinmiş.';
        }

        $gorev->update([
            'durum' => $iz['durum'],
            'adimlar' => $iz['adimlar'],
            'ilerleme_notlari' => array_slice($gorev->ilerleme_notlari ?? [], 0, (int) $iz['not_sayisi']),
            'son_islem_at' => now(),
        ]);

        return "\"{$gorev->baslik}\" güncellemeden önceki hâline döndürüldü.";
    }

    public function ornekler(): array
    {
        return [
            '5 numaralı görevin 2. adımını yapıldı işaretle',
            'kullanıcı bulma görevine not düş: TUSU\'ya taslak hazırlandı',
            '3 numaralı görevi tamamlandı olarak kapat',
        ];
    }
}
