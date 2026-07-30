<?php

namespace App\Ai\Kahya\Araclar;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Kâhya'nın okuma halkasındaki gözü: izin listeli tablolarda güvenli sorgu.
 *
 * "Üst kategori id'sini bilmiyorum, sisteme bakamıyorum" çağının kapanışı —
 * model artık bir eylemi çağırmadan önce ihtiyacı olan kaydı kendisi bulur.
 *
 * GÜVENLİK SINIRLARI (tasarım: 2026-07-30 Kâhya 2.0, "okuma halkası"):
 * - Tablo VE kolon izin listelidir. `users.password` gibi sır kolonları listede
 *   yoktur; listede olmayan hiçbir şey seçilemez, filtrelenemez.
 * - SQL model tarafından YAZILMAZ: sorgu query builder ile parametreli kurulur.
 *   Modelden gelen her değer bağlı parametredir (bindings).
 * - Yalnız SELECT üretir; limit tavanı kodda. Yazma araçları ayrı halkada ve
 *   denetim izi zorunluluğuyla yaşar.
 */
class TabloSorgula implements Tool
{
    private const LIMIT_TAVANI = 50;

    /**
     * Tablo → seçilebilir kolonlar. Buradaki liste hem SELECT hem WHERE için
     * tek yetkidir. `users` bilerek dar: sır/kişisel alan yok (parola, 2FA,
     * kurtarma kodları, e-posta*, telefon). *E-posta admin panelde görünse de
     * sohbet dökümüne sızmasın diye dışarıda tutuldu — ihtiyaç olursa sahibin
     * kararıyla açılır.
     *
     * @var array<string, list<string>>
     */
    private const TABLOLAR = [
        'categories' => ['id', 'parent_id', 'name', 'slug', 'icon', 'type', 'sort_order', 'is_active'],
        'tags' => ['id', 'name', 'slug'],
        'countries' => ['code', 'name_tr', 'emoji', 'default_currency', 'is_active', 'sort_order'],
        'cities' => ['id', 'country_code', 'name', 'sort_order', 'is_active'],
        'currencies' => ['code', 'name', 'symbol', 'is_active', 'sort_order'],
        'listings' => ['id', 'user_id', 'category_id', 'type', 'title', 'slug', 'price', 'currency', 'price_unit', 'country_code', 'city', 'status', 'is_featured', 'views_count', 'is_demo', 'created_at'],
        'users' => ['id', 'name', 'username', 'country_code', 'city', 'role', 'is_verified', 'status', 'account_type', 'is_demo', 'created_at'],
        // Kâhya'nın kendi kalıcı hafızası (F1) — yönergeye sığmayan kayıtlar
        // buradan aranır; `unut` için id de buradan bulunur.
        'kahya_hafiza' => ['id', 'tur', 'metin', 'kaynak', 'aktif', 'created_at'],
    ];

    /** Araç adı — katalogdaki eylem adlarıyla aynı biçimde (kebab-case). */
    public function name(): string
    {
        return 'tablo-sorgula';
    }

    public function description(): Stringable|string
    {
        $tablolar = implode(' · ', array_map(
            fn (string $tablo, array $kolonlar): string => "{$tablo} (".implode(', ', $kolonlar).')',
            array_keys(self::TABLOLAR),
            self::TABLOLAR,
        ));

        return 'Sitenin veritabanında güvenli okuma yapar. Bir eylem için id/kod gerektiğinde '
            .'(ör. üst kategori id\'si, ülke kodu) ÖNCE bu araçla bak, sonra eylemi çağır. '
            .'Sorgulanabilir tablolar ve kolonları: '.$tablolar.'. '
            .'Sonuç JSON satır listesidir; sayim=true ile yalnız adet döner.';
    }

    public function handle(Request $request): Stringable|string
    {
        $tablo = (string) $request['tablo'];
        $kolonlar = self::TABLOLAR[$tablo] ?? null;

        if ($kolonlar === null) {
            return 'HATA: "'.$tablo.'" sorgulanabilir tablolardan değil. İzinli tablolar: '
                .implode(', ', array_keys(self::TABLOLAR)).'.';
        }

        $sorgu = DB::table($tablo)->select($kolonlar);

        // Koşullar: yalnız izinli kolonlarda eşitlik. Bilinmeyen kolon sessizce
        // atlanmaz — model yanlış filtreyle "sonuç yok" sanmasın diye söylenir.
        $kosullar = $request['kosullar'] ?? [];
        if (is_array($kosullar)) {
            foreach ($kosullar as $kolon => $deger) {
                if (! in_array($kolon, $kolonlar, true)) {
                    return "HATA: \"{$kolon}\" kolonu {$tablo} tablosunda sorgulanamaz. İzinli kolonlar: ".implode(', ', $kolonlar).'.';
                }
                if (! is_scalar($deger) && $deger !== null) {
                    return "HATA: \"{$kolon}\" koşulu düz bir değer olmalı.";
                }
                $sorgu->where($kolon, $deger);
            }
        }

        // Ad araması: LIKE ile kısmi eşleşme (ör. "Ev" → "Ev & Tamir").
        $ara = trim((string) ($request['ara'] ?? ''));
        if ($ara !== '') {
            $adKolonu = in_array('name', $kolonlar, true) ? 'name'
                : (in_array('name_tr', $kolonlar, true) ? 'name_tr'
                : (in_array('title', $kolonlar, true) ? 'title'
                : (in_array('metin', $kolonlar, true) ? 'metin' : null)));

            if ($adKolonu === null) {
                return "HATA: {$tablo} tablosunda ad araması yapılamaz.";
            }

            $sorgu->where($adKolonu, 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $ara).'%');
        }

        if ($request['sayim'] ?? false) {
            return (string) $sorgu->count();
        }

        $limit = min(max((int) ($request['limit'] ?? 20), 1), self::LIMIT_TAVANI);
        $satirlar = $sorgu->limit($limit)->get();

        if ($satirlar->isEmpty()) {
            return '(Sonuç yok.)';
        }

        /*
         * Kullanım sayacı yalnız ARANIP BULUNAN hafıza kayıtlarında artar
         * (yönergeye gömülenler sayılmaz): hangi bilginin gerçekten işe
         * yaradığını bu ayrım gösterir — F5 ders-cikar'ın hammaddesi.
         * Salt-okunur halkada tek istisna bu sayaçtır ve bilinçli: veri
         * değil, verinin KULLANIM ölçümü yazılıyor.
         */
        if ($tablo === 'kahya_hafiza') {
            DB::table('kahya_hafiza')
                ->whereIn('id', $satirlar->pluck('id'))
                ->increment('kullanim_sayisi');
        }

        return $satirlar->toJson(JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tablo' => $schema->string()
                ->enum(array_keys(self::TABLOLAR))
                ->description('Sorgulanacak tablo.')
                ->required(),
            'kosullar' => $schema->object()
                ->description('İsteğe bağlı eşitlik filtreleri: {"kolon": deger}. Ör. {"parent_id": null, "is_active": true}.'),
            'ara' => $schema->string()
                ->description('İsteğe bağlı ad araması (kısmi eşleşme). Ör. "Ev" → adında Ev geçen kayıtlar.'),
            'limit' => $schema->integer()
                ->description('En çok kaç satır (varsayılan 20, tavan '.self::LIMIT_TAVANI.').'),
            'sayim' => $schema->boolean()
                ->description('true ise satırlar yerine yalnız adet döner.'),
        ];
    }
}
