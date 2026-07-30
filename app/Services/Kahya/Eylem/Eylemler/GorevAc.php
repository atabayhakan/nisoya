<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\KahyaGorevi;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Görev defterine yeni bir misyon açar (F2 — tasarım §2.3).
 *
 * Görev ile sohbet arasındaki fark ZAMAN ölçeğidir: sohbet turu dakikalar
 * içinde biter, görev haftalar sürebilir. "Gerçek kullanıcı bul" gibi bir
 * misyon sohbette söylenip unutulacaksa hiç söylenmemiş demektir — buraya
 * yazılan, günlük raporda görünmeye devam eder.
 */
class GorevAc extends Eylem
{
    public function ad(): string
    {
        return 'gorev-ac';
    }

    public function baslik(): string
    {
        return 'Görev aç';
    }

    public function aciklama(): string
    {
        return 'Görev defterine uzun vadeli bir iş (misyon) açar: hedef + adım planı. Sahip '
            .'haftalar sürecek bir iş istediğinde ("kullanıcı bul", "SEO\'yu baştan sona '
            .'iyileştir") kullan — TEK SEFERLİK işler için KULLANMA, onları doğrudan yap. '
            .'Adım planını SEN tasarla: hedefi 3-8 somut adıma böl. Açık görevlerin '
            .'"Görevlerin" bölümünde durur; ilerlemeyi gorev-guncelle ile işlersin.';
    }

    public function sema(): array
    {
        return [
            'baslik' => 'Görevin kısa adı. Ör. "Gerçek kullanıcı bulma misyonu".',
            'hedef' => 'Bitince neyin değişmiş olacağı — ölçülebilir tek cümle. '
                .'Ör. "Pazaryerinde sahibin dışında 10 gerçek satıcı olacak."',
            'adimlar' => 'Adım planı: her satıra bir adım (satır sonuyla ayır). '
                .'3-8 somut, sıralı adım.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'baslik' => ['required', 'string', 'min:5', 'max:150'],
            'hedef' => ['required', 'string', 'min:10', 'max:500'],
            'adimlar' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function risk(): EylemRiski
    {
        // Defter kaydı — dışarıya hiçbir şey gitmiyor, geri alması tek tık.
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $adimSayisi = count($this->adimlariAyristir($p['adimlar'] ?? null));

        return "\"{$p['baslik']}\" görevi açılacak ({$adimSayisi} adımlı plan). Hedef: {$p['hedef']}";
    }

    public function uygula(array $p): array
    {
        $gorev = KahyaGorevi::create([
            'baslik' => (string) $p['baslik'],
            'hedef' => (string) $p['hedef'],
            'durum' => KahyaGorevi::DURUM_ACIK,
            'adimlar' => $this->adimlariAyristir($p['adimlar'] ?? null),
            'son_islem_at' => now(),
        ]);

        return [
            'sonuc' => "\"{$gorev->baslik}\" görevi açıldı (#{$gorev->id}, ".count($gorev->adimlar ?? []).' adım).',
            'geri_alma' => ['id' => $gorev->id],
        ];
    }

    public function geriAl(array $iz): string
    {
        $gorev = KahyaGorevi::query()->find($iz['id'] ?? 0);

        if ($gorev === null) {
            return 'Görev zaten yok.';
        }

        /*
         * İLERLEME BİRİKMİŞSE SİLİNMEZ, İPTAL EDİLİR: açılış ile geri alma
         * arasında adım işlenmiş ya da hamle bağlanmış olabilir; o izi yok
         * etmek denetim defterini deler. Bağlı hamlelerin FK'sı nullOnDelete
         * ile çözülürdü ama kararların bağlamı kaybolurdu.
         */
        if (($gorev->ilerleme_notlari ?? []) !== [] || $gorev->hamleler()->exists()) {
            $gorev->update(['durum' => KahyaGorevi::DURUM_IPTAL, 'son_islem_at' => now()]);

            return "\"{$gorev->baslik}\" silinmedi (ilerleme izi var) — iptal edildi.";
        }

        $baslik = $gorev->baslik;
        $gorev->delete();

        return "\"{$baslik}\" görevi silindi.";
    }

    public function ornekler(): array
    {
        return [
            'yeni bir görev aç: gerçek satıcılar bul, planını da sen çıkar',
            'önümüzdeki ay için SEO iyileştirme misyonu başlat',
        ];
    }

    /** @return list<array{metin: string, durum: string}> */
    private function adimlariAyristir(?string $metin): array
    {
        if ($metin === null || trim($metin) === '') {
            return [];
        }

        return collect(preg_split('/\R+/', $metin) ?: [])
            ->map(fn (string $satir): string => trim(preg_replace('/^\s*(?:\d+[\.\)]\s*|[-·*]\s*)/u', '', $satir) ?? ''))
            ->filter(fn (string $satir): bool => $satir !== '')
            ->map(fn (string $satir): array => ['metin' => $satir, 'durum' => 'bekliyor'])
            ->values()
            ->all();
    }
}
