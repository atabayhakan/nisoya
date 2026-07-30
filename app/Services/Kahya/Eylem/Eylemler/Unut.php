<?php

namespace App\Services\Kahya\Eylem\Eylemler;

use App\Enums\EylemRiski;
use App\Models\KahyaHafizasi;
use App\Services\Kahya\Eylem\Eylem;

/**
 * Bir hafıza kaydını pasife çeker (F1 — tasarım §2.3).
 *
 * SİLMEZ, pasife çeker: "unut" da geri alınabilir olmalı (bu evin kuralı).
 * Kalıcı silme panelden — sahip, listeyi görerek siler; sohbette yanlış
 * kaydı hedeflemiş bir "unut"un bedeli pasife çekmekte küçük, silmekte
 * büyük olurdu.
 */
class Unut extends Eylem
{
    public function ad(): string
    {
        return 'unut';
    }

    public function baslik(): string
    {
        return 'Hafızadan düşür';
    }

    public function aciklama(): string
    {
        return 'Kalıcı hafızadaki bir kaydı pasife çeker; artık yönergene girmez ama silinmez '
            .'(sahip panelden kalıcı silebilir). Sahip "şunu unut", "o kural artık geçersiz" '
            .'dediğinde kullan. Kaydın id\'si gerekir — Hatırladıkların bölümünde id yazar; '
            .'orada yoksa tablo-sorgula ile kahya_hafiza tablosunda ara. Hangi kaydı '
            .'kastettiğinden emin değilsen ÖNCE sor.';
    }

    public function sema(): array
    {
        return [
            'id' => 'Pasife çekilecek hafıza kaydının sayısal kimliği.',
        ];
    }

    public function kurallar(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:kahya_hafiza,id'],
        ];
    }

    public function risk(): EylemRiski
    {
        return EylemRiski::Dusuk;
    }

    public function onizleme(array $p): string
    {
        $kayit = KahyaHafizasi::query()->findOrFail($p['id']);

        return "Hafızadan düşürülecek ({$kayit->tur->etiket()}): \"{$kayit->metin}\"";
    }

    public function uygula(array $p): array
    {
        $kayit = KahyaHafizasi::query()->findOrFail($p['id']);

        if (! $kayit->aktif) {
            return [
                'sonuc' => "Bu kayıt zaten pasifti: \"{$kayit->metin}\"",
                // Zaten pasif kaydı geri almak onu AKTİFLEŞTİRMEMELİ —
                // iz "değişiklik yok" taşır, geriAl buna göre davranır.
                'geri_alma' => ['id' => $kayit->id, 'degisiklik' => false],
            ];
        }

        $kayit->update(['aktif' => false]);

        return [
            'sonuc' => "Hafızadan düşürüldü: \"{$kayit->metin}\" (panelden kalıcı silebilirsin).",
            'geri_alma' => ['id' => $kayit->id, 'degisiklik' => true],
        ];
    }

    public function geriAl(array $iz): string
    {
        if (! ($iz['degisiklik'] ?? true)) {
            return 'Değişiklik yoktu; bir şey yapılmadı.';
        }

        $kayit = KahyaHafizasi::query()->find($iz['id'] ?? 0);

        if ($kayit === null) {
            return 'Kayıt bu arada panelden silinmiş.';
        }

        $kayit->update(['aktif' => true]);

        return "Kayıt hafızaya geri alındı: \"{$kayit->metin}\"";
    }

    public function ornekler(): array
    {
        return [
            'şu 12 numaralı hatırlattığımı unut',
            'Körfez kuralını unut, artık orası da öncelik',
        ];
    }
}
