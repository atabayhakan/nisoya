<?php

namespace App\Services\Demo;

use App\Models\DemoKaydi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Demo üretiminin defteri: ne ürettiysek buraya yazılır.
 *
 * ÜRETİM SIRASI KAYIT SIRASIDIR, silme ise TERS SIRADIR. Bu tesadüf değil:
 * çocuk kayıtlar ebeveynlerinden sonra üretildiği için, ters sırada silmek
 * çocukları önce siler. Ebeveyni önce silseydik veritabanı cascade'i devreye
 * girer, Eloquent baypas edilir ve gözlemciler çalışmadan dosyalar diskte
 * kalırdı — üstelik `conversations.listing_id` gibi nullOnDelete bağlar
 * sessizce yetim satır bırakırdı.
 */
class DemoDefteri
{
    /**
     * Yeni bir parti kimliği. İnsan okuyabilsin diye tarih önde; sonundaki
     * rastgele parça aynı gün içindeki ikinci üretimi ayırır.
     */
    public function yeniParti(): string
    {
        return now()->format('Y-m-d').'-'.Str::lower(Str::random(6));
    }

    /**
     * Bir modeli deftere yaz.
     *
     * @param  array<int, ?string>  $dosyalar  Bu kayıt için diske yazılan yollar
     */
    public function kaydet(string $parti, Model $model, array $dosyalar = []): DemoKaydi
    {
        $temizDosyalar = array_values(array_filter($dosyalar));

        return DemoKaydi::create([
            'parti' => $parti,
            'model_turu' => $model::class,
            'model_id' => (int) $model->getKey(),
            'dosyalar' => $temizDosyalar === [] ? null : $temizDosyalar,
        ]);
    }

    /**
     * Partiler ve içerikleri — panelin ve `demo:durum` komutunun kaynağı.
     *
     * @return list<array{parti: string, olusturuldu: ?string, adet: int, dokum: array<string, int>}>
     */
    public function partiler(): array
    {
        return DemoKaydi::query()
            ->orderByDesc('id')
            ->get()
            ->groupBy('parti')
            ->map(fn ($kayitlar, $parti): array => [
                'parti' => (string) $parti,
                'olusturuldu' => $kayitlar->min('created_at')?->toAtomString(),
                'adet' => $kayitlar->count(),
                'dokum' => $kayitlar
                    ->groupBy('model_turu')
                    ->map->count()
                    ->mapWithKeys(fn (int $adet, string $sinif): array => [class_basename($sinif) => $adet])
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** Bir partinin defter satırları, SİLME sırasında (en yeniden en eskiye). */
    public function silmeSirasi(string $parti): Collection
    {
        return DemoKaydi::query()->parti($parti)->orderByDesc('id')->get();
    }

    public function partiVarMi(string $parti): bool
    {
        return DemoKaydi::query()->parti($parti)->exists();
    }
}
