<?php

namespace App\Services\Rehber;

use App\Models\KahyaMesaji;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rehberde eksik olan sayfaları GERÇEK SORULARDAN bulur.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Tek kişilik bir ekipte dokümantasyon ölür: sahip neyi yazacağını tahmin
 * eder, tahmin ettiği şey zaten bildiği şeydir, bilmediği şey hiç yazılmaz.
 *
 * Kâhya'ya "kaynak gösteremiyorsan cevap verme" kuralı konuldu; rehberde
 * olmayan bir konu sorulduğunda o cümleyi kuruyor. Bu cümle bir HATA değil,
 * bir SİNYAL: sahip o konuyu merak etti ve rehberde yoktu.
 *
 * Avcı bu cevapları toplar ve ONDAN ÖNCEKİ soruyu yanına koyar. Doküman
 * backlog'u böylece tahminden değil gerçek ihtiyaçtan türer.
 *
 * ---------------------------------------------------------------------------
 * NEDEN BASİT METİN EŞLEŞMESİ
 *
 * Kâhya'nın kuracağı cümle yönergede BİREBİR tanımlı ("rehberde bu konu yok").
 * Sinyali yakalamak için ayrı bir tablo, olay ya da model çağrısı gerekmiyor.
 * Model bu ifadeyi değiştirirse avcı körelir — bu yüzden ifade hem yönergede
 * hem burada aynı sabitten okunur.
 */
class RehberBoslukAvcisi
{
    /**
     * Kâhya'nın "bilmiyorum" demek için kurduğu cümlenin çekirdeği.
     * Yönergede de aynı ifade geçiyor (bkz. KahyaAjani::instructions).
     */
    public const ISARET = 'rehberde bu konu yok';

    /**
     * Son N gündeki boşluklar: Kâhya'nın kaynaksız kaldığı sorular.
     *
     * @return Collection<int, array{soru: string, tarih: Carbon}>
     */
    public function bosluklar(int $gun = 30, int $limit = 20): Collection
    {
        $mesajlar = KahyaMesaji::query()
            ->where('created_at', '>=', now()->subDays($gun))
            ->orderBy('id')
            ->get(['id', 'rol', 'metin', 'created_at']);

        $bosluklar = collect();
        $sonSoru = null;

        foreach ($mesajlar as $mesaj) {
            if ($mesaj->rol === KahyaMesaji::ROL_SAHIP) {
                $sonSoru = $mesaj;

                continue;
            }

            if ($sonSoru === null) {
                continue;
            }

            if (! str_contains(mb_strtolower($mesaj->metin), self::ISARET)) {
                continue;
            }

            $bosluklar->push([
                'soru' => Str::limit(trim($sonSoru->metin), 160),
                'tarih' => $mesaj->created_at,
            ]);

            // Aynı soru tekrar tekrar sayılmasın: cevaplandıktan sonra sıfırla.
            $sonSoru = null;
        }

        return $bosluklar->reverse()->take($limit)->values();
    }

    public function sayi(int $gun = 30): int
    {
        return $this->bosluklar($gun, PHP_INT_MAX)->count();
    }
}
