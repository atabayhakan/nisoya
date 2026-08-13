<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Otomatik Türkçe Küfür, Hakaret ve Saldırgan İfade Filtresi.
 *
 * Mesajlar, İlanlar, Yorumlar ve Profil alanlarında küfürlü/saldırgan
 * ifadeleri leetspeak ve harf manipülasyonlarını aşarak tespit eder.
 */
class ProfanityFilterService
{
    /**
     * Temel Türkçe ve genel küfür / hakaret / argo sözlüğü.
     * Patlatma/gizleme amaçlı kök kelimeler.
     */
    /**
     * Ağır küfür kökleri.
     *
     * -------------------------------------------------------------------------
     * ÜÇ KELİME ÇIKARILDI (2026-08-13, canlıda ölçüldü): `kopek`, `top`, `it`.
     *
     * Bunlar bağlama göre hakaret olabilir ama HER BİRİ sıradan Türkçe kelime
     * ve engellenmeleri gerçek kullanımı kırıyordu. Ölçüm:
     *
     *   "Köpek bakıcısı arıyorum, haftada iki gün"  -> ENGELLENDİ
     *   "Çocuklar için top satıyorum"                -> ENGELLENDİ
     *   "Sitede top model kıyafet var"               -> ENGELLENDİ
     *
     * Filtre yalnız sohbette değil İLAN OLUŞTURURKEN de çalışıyor
     * (ListingRequest), yani "köpek bakıcılığı yapıyorum" diye ilan
     * VERİLEMİYORDU. Site bakım hizmetleri pazaryeri — "Bebek Bakıcılığı" ve
     * "Yaşlı Bakımı" kategorileri var; hayvan bakımı da doğal bir komşusu.
     *
     * Takas net: zayıf bir hakareti geçirmek, dürüst satıcının ilanını
     * reddetmekten iyidir. Hakaret için şikâyet mekanizması zaten var.
     *
     * Kısa kelimeler (`am`, `oc`, `got`, `dol`) YALNIZ TEK BAŞINAYKEN eşleşir
     * (aşağıdaki eşleştirme mantığı), o yüzden "Ocak", "toplam", "tamam",
     * "dolap" güvende — ölçüldü.
     */
    private const BAD_WORDS = [
        'amk', 'aq', 'amq', 'amw', 'a.m.k', 'a.q',
        'sik', 'siki', 'sikis', 'sikis', 'sikerim', 'sikeyim', 'sikti', 'siktir', 'sikim', 'sikimi',
        'orospu', 'oroç', 'orosbu', 'o.ç', 'oc', 'o.c',
        'am', 'ami', 'amci', 'amcik', 'amcuk',
        'got', 'gotu', 'gotun', 'gotveren', 'gotlek',
        'pic', 'pici', 'pich',
        'yarrak', 'yarak', 'yarragim', 'yarram',
        'ibne', 'ipne',
        'kahpe', 'puşt', 'pust',
        'yavsak', 'yavşak', 'döl', 'dol',
        'fahişe', 'fahise', 'kaltak',
        'gavat', 'kavat', 'piç',
    ];

    /**
     * Leetspeak sembol dönüşüm haritası.
     */
    private const LEET_MAP = [
        '@' => 'a', '4' => 'a', '4r' => 'ar',
        '3' => 'e', '€' => 'e',
        '1' => 'i', '!' => 'i', '|' => 'i', 'i̇' => 'i',
        '0' => 'o', 'ö' => 'o',
        '$' => 's', '5' => 's', 'ş' => 's',
        '7' => 't', '+' => 't',
        'u' => 'u', 'ü' => 'u',
        'c' => 'c', 'ç' => 'c',
        'g' => 'g', 'ğ' => 'g',
    ];

    /**
     * Metinde küfür/hakaret var mı?
     */
    public function containsProfanity(?string $text): bool
    {
        if ($text === null || trim($text) === '') {
            return false;
        }

        return ! empty($this->findProfanities($text));
    }

    /**
     * Metinde geçen küfürlü kelimeleri döndürür.
     *
     * @return array<int, string>
     */
    public function findProfanities(string $text): array
    {
        $normalized = $this->normalize($text);
        $words = preg_split('/[\s\-_,\.\;\:\/\?\!\+\*\#\%\&\(\)]+/', $normalized) ?: [];
        $found = [];

        foreach ($words as $word) {
            $cleaned = preg_replace('/(.)\1+/', '$1', $word); // Tekrarlanan harfleri teke indir (ör: sssiiikk -> sik)

            foreach (self::BAD_WORDS as $badWord) {
                if ($word === $badWord || $cleaned === $badWord) {
                    $found[] = $badWord;

                    continue;
                }

                // Çok karakterli küfür köklerinde kelime içi eşleşme
                if (strlen($badWord) >= 4 && (str_contains($word, $badWord) || str_contains((string) $cleaned, $badWord))) {
                    $found[] = $badWord;
                }
            }
        }

        // Doğrudan cümle içi tam eşleşme kontrolleri (ör. "a.m.k", "o.ç")
        foreach (self::BAD_WORDS as $badWord) {
            if (str_contains($normalized, ' '.$badWord.' ') || str_starts_with($normalized, $badWord.' ') || str_ends_with($normalized, ' '.$badWord)) {
                $found[] = $badWord;
            }
        }

        return array_unique($found);
    }

    /**
     * Metindeki küfürlü kelimeleri sansürler.
     */
    public function censor(string $text, string $mask = '***'): string
    {
        $found = $this->findProfanities($text);

        if (empty($found)) {
            return $text;
        }

        $result = $text;
        foreach ($found as $badWord) {
            $pattern = '/'.preg_quote($badWord, '/').'/i';
            $result = (string) preg_replace($pattern, $mask, $result);
        }

        return $result;
    }

    /**
     * Metni doğrular. Küfür tespit edilirse kullanıcıya gösterilecek Türkçe hatayı döner, temizse null döner.
     */
    public function validateText(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        if ($this->containsProfanity($text)) {
            return 'Yazdığınız metin topluluk kurallarına aykırı (küfür/hakaret) ifadeler içermektedir. Lütfen düzenleyip tekrar deneyin.';
        }

        return null;
    }

    /**
     * Leetspeak ve özel karakterleri temizleyip normalize eder.
     */
    private function normalize(string $text): string
    {
        $lower = Str::lower(str_replace(['İ', 'I', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'], ['i', 'i', 'g', 'u', 's', 'o', 'c'], $text));
        $replaced = strtr($lower, self::LEET_MAP);

        // Alfanümerik ve boşluk dışındaki karakterleri temizle
        return (string) preg_replace('/[^a-z0-9\s\.\-_]/', '', $replaced);
    }
}
