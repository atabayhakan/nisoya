<?php

namespace App\Support;

/**
 * Görünüm ayarlarının TEK GERÇEK KAYNAĞI: hangi ayar anahtarı hangi temada
 * yaşar ve onu hangi bileşen okur.
 *
 * NEDEN VAR: 2026-07-28 denetiminde şu bulundu — admin panelindeki görünüm
 * kontrollerinin tamamı (marka rengi, ana renk, font, köşe, cam, animasyon)
 * Vitrin teması aktifken HİÇBİR ŞEY YAPMIYORDU. Sebep sessiz ve masumdu:
 * klasik iskelet <x-brand-theme /> + <x-tasarim-theme /> basıyor, Vitrin
 * iskeleti ise yalnız <x-vitrin-theme /> basıyor ve o bileşen hiçbir ayar
 * okumuyordu. Panelde hiçbir uyarı yoktu; sahip düğmeleri çeviriyor, site
 * değişmiyordu.
 *
 * Bu sınıf o hata sınıfını YAPISAL olarak imkânsız kılar: her kontrol burada
 * "hangi temalarda geçerli" ve "hangi dosya okuyor" diye beyan edilir,
 * TemaJetonlariTest de beyanın doğruluğunu makineyle doğrular — okuyan dosya
 * anahtarı gerçekten okuyor mu ve o dosya ilgili temanın iskeletinde
 * gerçekten basılıyor mu.
 *
 * Yeni bir görünüm ayarı eklerken buraya da eklenmezse özelleştiricide
 * görünmez; yanlış beyan edilirse test kırılır.
 */
final class TemaJetonlari
{
    /**
     * @var array<string, array{etiket: string, temalar: array<int, string>, okuyan: string, bilesen: string}>
     */
    public const JETONLAR = [
        'gorunum.marka_rengi' => [
            'etiket' => 'Marka rengi (hazır aile)',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/brand-theme.blade.php',
            'bilesen' => 'x-brand-theme',
            'bilesen_dosyasi' => 'resources/views/components/brand-theme.blade.php',
        ],
        'gorunum.primary_color' => [
            'etiket' => 'Özel birincil renk',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.font_family' => [
            'etiket' => 'Yazı tipi',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.border_radius' => [
            'etiket' => 'Köşe yuvarlatma',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.glassmorphism' => [
            'etiket' => 'Cam efekti',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.smooth_animations' => [
            'etiket' => 'Akıcı animasyonlar',
            'temalar' => ['klasik'],
            'okuyan' => 'resources/views/components/tasarim-theme.blade.php',
            'bilesen' => 'x-tasarim-theme',
            'bilesen_dosyasi' => 'resources/views/components/tasarim-theme.blade.php',
        ],
        'gorunum.vitrin_aksan' => [
            'etiket' => 'Vurgu rengi',
            'temalar' => ['vitrin'],
            // Anahtar bu sınıfın kendi içinde okunur (vitrinAksani); bileşen
            // ona dolaylı olarak bağlanır. Bekçi testi zincirin üç halkasını
            // da ayrı ayrı doğrular.
            'okuyan' => 'app/Support/TemaJetonlari.php',
            'bilesen' => 'x-vitrin-theme',
            'bilesen_dosyasi' => 'resources/views/components/vitrin-theme.blade.php',
        ],
        // Üçü de aynı bileşeni okur/basar; diğer jetonlardan farklı olarak
        // İKİ temada birden geçerli — başlıktaki marka yazısı her iki
        // iskelette de var (2026-08-19, sahibin isteği).
        'gorunum.logo_animasyon' => [
            'etiket' => 'Hareketli logo yazısı',
            'temalar' => ['klasik', 'vitrin'],
            // Anahtar bu sınıfın kendi içinde okunur (logoAnimasyonuAktifMi);
            // iskeletler ona dolaylı bağlanır (bkz. vitrin_aksan'daki aynı desen).
            'okuyan' => 'app/Support/TemaJetonlari.php',
            'bilesen' => 'x-hareketli-logo',
            'bilesen_dosyasi' => 'resources/views/components/hareketli-logo.blade.php',
        ],
        'gorunum.logo_rengi' => [
            'etiket' => 'Logo yazısı rengi',
            'temalar' => ['klasik', 'vitrin'],
            'okuyan' => 'resources/views/components/hareketli-logo.blade.php',
            'bilesen' => 'x-hareketli-logo',
            'bilesen_dosyasi' => 'resources/views/components/hareketli-logo.blade.php',
        ],
        'gorunum.logo_yazi_tipi' => [
            'etiket' => 'Logo el yazısı fontu',
            'temalar' => ['klasik', 'vitrin'],
            'okuyan' => 'resources/views/components/hareketli-logo.blade.php',
            'bilesen' => 'x-hareketli-logo',
            'bilesen_dosyasi' => 'resources/views/components/hareketli-logo.blade.php',
        ],
    ];

    /**
     * Seçilebilir yazı tiplerinin TEK KAYNAĞI.
     *
     * NEDEN BURADA: panelde uzun süre 'inter' ve 'outfit' seçenekleri durdu;
     * ikisi de hiçbir yerden YÜKLENMİYORDU (vite.config.js yalnız Instrument
     * Sans, Instrument Serif ve Plus Jakarta Sans'ı self-host ediyor). Sahip
     * seçiyor, kaydediliyor, site sessizce sistem sans'ına düşüyordu. Üstelik
     * "obsidian" ve "nordic" hazır ayarları o ölü değerleri kendiliğinden
     * yazıyordu. Liste tek yerde tutulur ve TemaJetonlariTest her seçeneğin
     * gerçekten self-host edildiğini vite.config.js'ten doğrular.
     *
     * @var array<string, array{etiket: string, aile: string, css: string}>
     */
    public const FONTLAR = [
        'sans' => [
            'etiket' => 'Instrument Sans (varsayılan)',
            'aile' => 'Instrument Sans',
            'css' => "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
        ],
        'serif' => [
            'etiket' => 'Instrument Serif',
            'aile' => 'Instrument Serif',
            'css' => "'Instrument Serif', Georgia, 'Times New Roman', serif",
        ],
    ];

    /** Geçerli font anahtarı mı? Değilse varsayılana düşülür. */
    public static function fontCss(?string $anahtar): string
    {
        return (self::FONTLAR[$anahtar] ?? self::FONTLAR['sans'])['css'];
    }

    /**
     * Panel açılır menüsü için anahtar => etiket.
     *
     * @return array<string, string>
     */
    public static function fontSecenekleri(): array
    {
        return array_map(fn (array $f) => $f['etiket'], self::FONTLAR);
    }

    /**
     * Hareketli logo yazısı için ÖNCEDEN ÜRETİLMİŞ SVG çizim yolları.
     *
     * -----------------------------------------------------------------------
     * NEDEN ÖNCEDEN ÜRETİLMİŞ (2026-08-19)
     *
     * "Nisoya" kelimesinin el yazısıyla çizilen hâli, tarayıcıda fontu indirip
     * anlık trace etmek yerine (bkz. reddedilen React örneği: opentype.js +
     * her ziyarette font indirme) BİR KEZ, derleme zamanında opentype.js ile
     * üretildi. Tarayıcıya giden tek şey düz bir `<path d="...">` dizesi —
     * yeni bir çalışma zamanı bağımlılığı yok, font indirme yok.
     *
     * Site adı (`genel.site_adi`) sabit "Nisoya" varsayımıyla üretildi;
     * bileşen bunu kontrol eder, uyuşmazsa animasyonlu yazı yerine düz metne
     * düşer (bkz. hareketli-logo.blade.php) — yanlış kelime çizilmiş gibi
     * görünmez.
     *
     * ÜÇ FONT DENENDİ, İKİSİ ELENDİ: Caveat ve Patrick Hand, opentype.js'in
     * gelişmiş OpenType özelliklerini (kerning/cursive-bağlantı) tam
     * desteklememesi yüzünden "Nisoya" yerine yalnız "Ni" + bozuk bir işaret
     * çiziyordu — PNG'ye render edilip GÖZLE doğrulanarak yakalandı. Panelde
     * yalnız gerçekten çalışan iki seçenek var; TemaJetonlari::FONTLAR'daki
     * "inter/outfit hiç yüklenmiyordu" dersiyle aynı disiplin: çalışmayan
     * seçeneği listeye koyma.
     *
     * @var array<string, array{etiket: string, viewBox: string, yol: string}>
     */
    public const EL_YAZISI_FONTLAR = [
        'indie-flower' => [
            'etiket' => 'Indie Flower',
            'viewBox' => '0 30 300 124',
            'yol' => 'M48.20 50.20Q51.10 50.20 54.20 55.90Q57.30 61.60 59.40 68.95Q61.50 76.30 61.50 79.90Q61.50 85.40 59.15 89.75Q56.80 94.10 52.50 96.55Q48.20 99 42.70 99Q35.10 99 29.55 94.90Q24 90.80 20.70 85.45Q17.40 80.10 13.90 72.60L12.50 69.60L12.70 93.80Q12.70 96 11.95 98.05Q11.20 100.10 9.40 100.10Q7.60 100.10 6.80 98.05Q6 96 6 94Q6 87.60 7 75.60Q8 63.60 8 57.70Q8 56.30 7.80 54.80Q7.60 53.30 7.50 52.70Q7 50 7 47.90L7 47.50Q7.30 47.50 8.85 47.05Q10.40 46.60 10.70 46.60L11.70 46.60Q15.50 63.70 22.95 78.15Q30.40 92.60 43.40 92.60Q49.50 92.60 52.65 88.95Q55.80 85.30 55.80 79.10Q55.80 73.80 54.10 69.35Q52.40 64.90 49 58.40L46.30 53.10Q46 52.60 46 52.10Q46 51.30 46.60 50.75Q47.20 50.20 48.20 50.20M12.70 93.60L12.70 93.80L12.70 93.60M78.20 100.10Q75.90 100.10 74.50 93.35Q73.10 86.60 72.70 81.70Q72.30 76.80 71.60 66.20Q71.50 64.50 71.50 61.80Q71.50 58.40 72.05 57.10Q72.60 55.80 73.90 55.80Q75 55.80 75.65 56.25Q76.30 56.70 76.80 57.70L76.70 61.70Q76.70 68.10 77.35 73.80Q78 79.50 79.40 89.10Q80.20 93.30 80.50 96Q80.70 97.40 80 98.75Q79.30 100.10 78.20 100.10M75.70 45.10Q73.50 45.10 72.45 43.40Q71.40 41.70 71.40 39.20Q71.40 38.10 71.50 37.60Q71.40 36 73.30 36Q74.70 36 76.15 36.65Q77.60 37.30 77 37.60Q78 40.20 78 41.70Q78 43 77.40 43.75Q76.80 44.50 75.70 45.10M90 88.20Q91.40 87.10 92.70 87.10Q93.90 87.10 95.05 87.80Q96.20 88.50 97.40 89.55Q98.60 90.60 99.30 91.10Q101.60 92.90 105.60 93.90Q109.60 94.90 114.10 94.90Q119.20 94.90 123.25 92.95Q127.30 91 127.30 87.70Q127.30 84.70 125.10 82.95Q122.90 81.20 119.80 80.40Q116.70 79.60 111.60 78.90Q104.70 78.10 100.65 76.90Q96.60 75.70 94.20 72.95Q91.80 70.20 91.80 65.20Q91.80 59.80 96.90 57.15Q102 54.50 108.60 54.50Q114.60 54.50 118.85 57.05Q123.10 59.60 123.20 64.30Q123.40 67 122.40 68.55Q121.40 70.10 120 70.10Q119.20 70.10 118.65 69.50Q118.10 68.90 118.10 67.90Q118.10 66.30 119.20 64.40Q118.70 62 115.95 60.80Q113.20 59.60 109.40 59.60Q103.80 59.60 100.50 61.65Q97.20 63.70 97.20 66.50Q97.20 69.50 99.60 71.10Q102 72.70 105.45 73.35Q108.90 74 115.30 74.60Q120.60 75.30 123.95 76.30Q127.30 77.30 129.60 80.15Q131.90 83 131.90 88.30Q131.90 93.80 126.80 96.95Q121.70 100.10 114.60 100.10Q105.50 100.10 98.35 97Q91.20 93.90 90 88.20M161.10 100.10Q155.20 100.10 150.45 97.45Q145.70 94.80 143.05 90.05Q140.40 85.30 140.40 79.20Q140.40 69.20 147.75 64.30Q155.10 59.40 165.80 59.40Q170.70 59.40 174.95 61.80Q179.20 64.20 181.80 68.70Q184.40 73.20 184.40 79.10Q184.40 85.50 181.10 90.25Q177.80 95 172.45 97.55Q167.10 100.10 161.10 100.10M160 94.40Q169.30 94.40 174.10 90.20Q178.90 86 178.90 77.90Q178.90 72.40 175.15 68.40Q171.40 64.40 164.70 64.40Q160.20 64.40 155.60 66.10Q151 67.80 148 71.20Q145 74.60 145 79.40Q145 83.40 147 86.85Q149 90.30 152.45 92.35Q155.90 94.40 160 94.40M219.80 147.30Q213 147.30 207.65 144.40Q202.30 141.50 199.10 137.70Q195.90 133.90 195.50 131.50Q195.10 129.30 196.55 128.10Q198 126.90 200.20 127.10Q203.30 134.50 207.60 138.50Q211.90 142.50 219.90 142.70Q225.50 142.80 228.85 135.55Q232.20 128.30 233.60 117.85Q235 107.40 235 97.30Q235 92.10 234.70 88.80Q227.10 98.10 215.30 98.10Q205.10 98.10 199.65 88.40Q194.20 78.70 192.60 66.50Q192.40 65.30 192.40 63.10Q192.40 56.80 196.50 56.80Q196.90 56.80 197.60 61.80Q198.70 69.70 200.30 76Q201.90 82.30 205.65 87.45Q209.40 92.60 215.60 92.60Q220.50 92.60 225.65 89.35Q230.80 86.10 233.10 82.10Q233.10 74.50 231.15 68.45Q229.20 62.40 229.10 62Q228.90 59.80 228.90 59.20Q228.90 57.20 229.55 56.30Q230.20 55.40 231.50 55.40Q232.90 55.40 234.70 61.10Q236.50 66.80 237.90 76Q239.30 85.20 239.80 95.20Q240 100.40 240 102.90Q240 122.10 235.25 134.70Q230.50 147.30 219.80 147.30M273.70 100Q263.30 100 255.65 96.35Q248 92.70 248 83.50Q248 80.20 250 77.10Q252 74 255.90 72.05Q259.80 70.10 265.40 70.10Q269.70 70.10 272.70 70.50Q275.70 70.90 279.60 72.70Q283.50 74.50 287.70 78.40Q286.10 71.50 282.90 66.30Q279.70 61.10 275.75 58.25Q271.80 55.40 268 55.40Q266.20 55.40 264.65 56.05Q263.10 56.70 260.90 57.90Q258.40 59.50 257.20 59.50Q256.70 59.50 256.35 59.15Q256 58.80 256 58.30Q256 54.30 259.55 52.10Q263.10 49.90 268.20 50.10Q275.20 50.50 281 56.05Q286.80 61.60 290.15 69.80Q293.50 78 293.50 85.90Q293.50 87.20 293.30 89.60L290.10 89.40Q286.30 81.70 280.35 78.45Q274.40 75.20 268.40 75.20Q260.70 75.20 257.40 77.50Q254.10 79.80 254.10 84.20Q254.10 88.80 258.70 91.60Q263.30 94.40 270.50 94.40Q274.50 94.40 276.90 93.95Q279.30 93.50 281.90 92.70Q284 92 285.60 91.70Q287.20 91.40 289.70 91.40Q292.50 91.40 293.25 91.80Q294 92.20 294 93.60Q294 94.60 289.90 96.10Q285.80 97.60 280.70 98.80Q275.60 100 273.70 100',
        ],
        'dancing-script' => [
            'etiket' => 'Dancing Script',
            'viewBox' => '-2 22 250 112',
            'yol' => 'M4.60 108.70Q5.80 103.90 7.60 98Q9.40 92.10 11.95 83.95Q14.50 75.80 17.90 64.60Q21.30 53.40 25.80 38.10Q26 37.60 26.25 36.35Q26.50 35.10 26.50 34Q26.50 33 26.15 32.35Q25.80 31.70 25 31.70Q22.60 31.70 20.45 33.55Q18.30 35.40 16.75 38.20Q15.20 41 14.30 44.05Q13.40 47.10 13.40 49.50Q13.40 51.50 13.90 53Q10.60 53 9.25 51.35Q7.90 49.70 7.90 47.30Q7.90 43.90 9.90 40.50Q11.90 37.10 15 34.25Q18.10 31.40 21.55 29.70Q25 28 27.90 28Q30.90 28 32.85 30.55Q34.80 33.10 36 37.55Q37.20 42 38 47.60Q38.80 53.20 39.50 59.20Q40.10 64.20 40.70 69.15Q41.30 74.10 41.90 78.55Q42.50 83 43 86.70Q43.50 90.40 43.90 92.80Q44.60 88.10 45.95 82.15Q47.30 76.20 48.95 69.75Q50.60 63.30 52.40 57.15Q54.20 51 55.75 45.85Q57.30 40.70 58.45 37.25Q59.60 33.80 60 32.90Q60.50 31.60 62.30 29.80Q64.10 28 66.30 28Q64.60 32.60 62.45 39.25Q60.30 45.90 58.10 53.65Q55.90 61.40 53.80 69.45Q51.70 77.50 50.05 85Q48.40 92.50 47.45 98.65Q46.50 104.80 46.40 108.70Q43.30 108.70 41.35 106.90Q39.40 105.10 38.60 100.60Q38.10 98 37.35 93.45Q36.60 88.90 35.75 83.30Q34.90 77.70 34 71.65Q33.10 65.60 32.25 59.80Q31.40 54 30.70 49.15Q30 44.30 29.50 41.10Q24.70 58.40 21.30 70.35Q17.90 82.30 15.55 89.85Q13.20 97.40 11.40 101.50Q9.60 105.60 8 107.15Q6.40 108.70 4.60 108.70M68.80 102.60Q64.90 102.60 63 100.15Q61.10 97.70 61.10 94.20Q61.10 91.90 61.95 88.40Q62.80 84.90 64.20 81.05Q65.60 77.20 67.45 73.80Q69.30 70.40 71.40 68.25Q73.50 66.10 75.50 66.10Q76.40 66.10 77.05 66.65Q77.70 67.20 77.70 68.30Q77.70 69.50 76.15 72.20Q74.60 74.90 72.50 78.40Q70.40 81.90 68.85 85.75Q67.30 89.60 67.30 93.10Q67.30 96.70 68.50 97.90Q69.70 99.10 72.20 99.10Q75.60 99.10 79.45 95.70Q83.30 92.30 87.60 83.80L88.50 84.80Q85.60 93.40 80.15 98Q74.70 102.60 68.80 102.60M80.80 56.80Q79.20 56.80 77.95 56Q76.70 55.20 76.70 53.50Q76.70 51.40 79.15 49.85Q81.60 48.30 84 48.30Q85.50 48.30 86.40 49Q87.30 49.70 87.30 51.40Q87.30 53.30 85.25 55.05Q83.20 56.80 80.80 56.80M96.20 105.10Q91.50 105.10 88.75 103.05Q86 101 84.85 98Q83.70 95 83.70 92Q83.70 89 84.50 87.40Q85.30 85.80 86.45 85Q87.60 84.20 88.65 83.80Q89.70 83.40 90.10 83Q93.10 79.70 95.60 76.10Q98.10 72.50 100 68.50L100 67.30Q100 63.60 101.25 62.05Q102.50 60.50 103.90 60.50Q104.70 60.50 105.10 60.90Q105.50 61.30 105.50 61.80Q105.50 62.30 105.20 63.40Q104.90 64.50 104.90 66.20Q104.90 69.50 106.20 73.35Q107.50 77.20 108.80 81.45Q110.10 85.70 110.10 90.30Q110.10 91.60 109.95 92.85Q109.80 94.10 109.50 95.20Q113 94.60 116.10 92.05Q119.20 89.50 122.10 83.90L123 84.90Q121.10 90.40 117.30 93.25Q113.50 96.10 109 96.80Q107.40 101.10 103.95 103.10Q100.50 105.10 96.20 105.10M96.40 101.60Q97.60 101.60 99.60 100.80Q101.60 100 102.70 96.70Q98.50 95.90 95.25 93.40Q92 90.90 90.40 88.40Q88.20 88.50 88.20 91.90Q88.20 95.50 90.35 98.55Q92.50 101.60 96.40 101.60M103.20 95.10Q103.40 94.10 103.50 93Q103.60 91.90 103.60 90.40Q103.60 85.70 102.45 81.05Q101.30 76.40 100.50 72.30Q98.20 76.10 95.65 79.55Q93.10 83 90.80 85.30Q92.30 88.50 95.45 91.30Q98.60 94.10 103.20 95.10M127.10 103.20Q122.80 103.20 121 100.55Q119.20 97.90 119.20 94.10Q119.20 91 120.20 87.70Q121.20 84.40 122.85 81.50Q124.50 78.60 126.55 76.80Q128.60 75 130.70 75Q131.20 75 131.75 75.15Q132.30 75.30 132.80 75.50Q131.90 76.30 130.65 78.45Q129.40 80.60 128.30 83.35Q127.20 86.10 126.45 88.80Q125.70 91.50 125.70 93.40Q125.70 96.40 126.70 97.75Q127.70 99.10 129.30 99.10Q131.40 99.10 134.30 96.85Q137.20 94.60 140 91.10Q137.20 88.80 135.65 85.05Q134.10 81.30 134.10 77.40Q134.10 74.10 135.25 71.20Q136.40 68.30 138.60 66.55Q140.80 64.80 143.90 64.80Q148 64.80 149.40 67Q150.80 69.20 150.80 72.50Q150.80 76.40 148.90 81.15Q147 85.90 144.10 90.30Q145.90 91.30 147.90 91.30Q149.50 91.30 151.50 90.65Q153.50 90 155.45 88.40Q157.40 86.80 158.60 84.10L159.80 85.10Q157.80 89.80 154.15 91.75Q150.50 93.70 147.20 93.70Q145.90 93.70 144.70 93.45Q143.50 93.20 142.40 92.70Q139 97.20 134.95 100.20Q130.90 103.20 127.10 103.20M141.80 88.50Q144.30 84.80 145.95 80.75Q147.60 76.70 147.60 73.10Q147.60 70.20 146.75 68.85Q145.90 67.50 144.60 67.50Q142.60 67.50 140.50 70.40Q138.40 73.30 138.40 77.70Q138.40 80.60 139.25 83.55Q140.10 86.50 141.80 88.50M161 128Q158.50 128 156.60 126.65Q154.70 125.30 154.70 122.70Q154.70 119.50 156.30 117Q157.90 114.50 160.60 112.40Q163.30 110.30 166.80 108.40Q170.30 106.50 174.10 104.60Q175.50 100.90 176.95 96.35Q178.40 91.80 179.70 86.30Q176.50 91.50 172.85 95.25Q169.20 99 165.10 99Q163.20 99 161.35 98.15Q159.50 97.30 158.30 95.50Q157.10 93.70 157.10 90.90Q157.10 88.10 158.05 84.40Q159 80.70 160.40 77.20Q161.80 73.70 163.30 71.40Q164.80 69.10 165.90 69.10Q166.30 69.10 167.35 69.40Q168.40 69.70 169.35 70.25Q170.30 70.80 170.30 71.40Q169.40 72.70 168.30 75.10Q167.20 77.50 166.15 80.45Q165.10 83.40 164.40 86.20Q163.70 89 163.70 91Q163.70 92.80 164.50 93.90Q165.30 95 167.30 95Q169.20 95 171.55 93.15Q173.90 91.30 176.25 88.40Q178.60 85.50 180.60 82.20Q180.90 81.10 181.15 79.95Q181.40 78.80 181.60 77.60Q181.90 76.20 183.05 74.35Q184.20 72.50 186.70 72.50Q187.20 72.50 187.70 72.55Q188.20 72.60 188.80 72.80Q188.80 74.40 187.85 78.75Q186.90 83.10 185.15 88.90Q183.40 94.70 181 100.80Q185.70 99 190.20 94.85Q194.70 90.70 197.50 83.80L198.80 84.80Q197.30 89.60 194.25 93.15Q191.20 96.70 187.40 99.25Q183.60 101.80 179.90 103.50Q177.30 109.90 174.25 115.50Q171.20 121.10 167.85 124.55Q164.50 128 161 128M160.50 124.80Q162 124.80 165.55 120.85Q169.10 116.90 173.10 107Q169 109.20 165.50 111.35Q162 113.50 159.90 116Q157.80 118.50 157.80 121.80Q157.80 122.60 158.40 123.70Q159 124.80 160.50 124.80M200.40 104.70Q197.30 104.70 195.15 102.65Q193 100.60 193 96.40Q193 92.80 194.60 88.65Q196.20 84.50 199 80.45Q201.80 76.40 205.35 73.05Q208.90 69.70 212.85 67.70Q216.80 65.70 220.70 65.70Q224.60 65.70 227.20 67.75Q229.80 69.80 229.80 73.20Q229.80 75.70 228.45 76.60Q227.10 77.50 224.90 77.50Q225.10 76.70 225.25 75.75Q225.40 74.80 225.40 74Q225.40 71.70 224.30 70.05Q223.20 68.40 220.50 68.40Q217.70 68.40 214.80 70.35Q211.90 72.30 209.20 75.55Q206.50 78.80 204.40 82.60Q202.30 86.40 201.10 90.10Q199.90 93.80 199.90 96.70Q199.90 100.60 202.60 100.60Q204.90 100.60 207.60 98.45Q210.30 96.30 213.15 92.95Q216 89.60 218.60 86Q221.20 82.40 223.20 79.40Q223.60 78.80 223.70 78.80Q224.30 78.90 225.25 79.20Q226.20 79.50 226.90 80Q227.60 80.50 227.60 81.30Q227.60 82.20 226.70 83.75Q225.80 85.30 224.70 87.30Q223.60 89.30 222.70 91.35Q221.80 93.40 221.80 95.20Q221.80 96.70 222.60 98.15Q223.40 99.60 225.20 99.60Q227.90 99.60 232.30 95.65Q236.70 91.70 241.20 83.80L242.20 84.80Q240.10 90.20 236.80 94.30Q233.50 98.40 229.75 100.70Q226 103 222.40 103Q218.70 103 216.95 100.80Q215.20 98.60 215.20 96Q215.20 95.40 215.30 94.65Q215.40 93.90 215.50 93.10Q211.20 98.90 207.65 101.80Q204.10 104.70 200.40 104.70',
        ],
    ];

    /** Geçerli el yazısı font anahtarı mı? Değilse Indie Flower'a düşülür. */
    public static function elYazisiFontu(?string $anahtar): array
    {
        return self::EL_YAZISI_FONTLAR[$anahtar] ?? self::EL_YAZISI_FONTLAR['indie-flower'];
    }

    /**
     * Panel açılır menüsü için anahtar => etiket.
     *
     * @return array<string, string>
     */
    public static function elYazisiSecenekleri(): array
    {
        return array_map(fn (array $f) => $f['etiket'], self::EL_YAZISI_FONTLAR);
    }

    /**
     * Hareketli logo yazısı bu istekte gösterilmeli mi?
     *
     * Ayar açık OLSA BİLE site adı tam olarak "Nisoya" değilse false döner:
     * çizim yolu (EL_YAZISI_FONTLAR) bu kelime için üretildi, site adı
     * değişirse (ör. admin panelden) yanlış kelime çizilmiş gibi görünmemesi
     * için düz metne düşülür — çağıran taraf (her iki header) bunu görüp
     * mevcut `{{ setting('genel.site_adi') }}` span'ini basmaya devam eder.
     */
    public static function logoAnimasyonuAktifMi(): bool
    {
        return Settings::get('gorunum.logo_animasyon', '0') === '1'
            && Settings::get('genel.site_adi', 'Nisoya') === 'Nisoya';
    }

    /**
     * Köşe yuvarlatma ölçeği — TEK KAYNAK.
     *
     * -----------------------------------------------------------------------
     * NEDEN BURAYA TAŞINDI (2026-08-06)
     *
     * Ölçek iki yerde ayrı ayrı yazılıydı: gerçek CSS'i basan
     * `components/tasarim-theme.blade.php` ve paneldeki "canlı önizleme".
     * Panelin yanında tam da bunu yasaklayan bir yorum duruyordu — "önizleme,
     * sitenin gerçekte uygulayacağı CSS'in AYNISINI kullanır; ayrı bir eşleme
     * tutmak ikisinin sessizce ayrışmasına yol açardı" — ama eşleme yine de
     * kopyalanmıştı ve ÇOKTAN AYRIŞMIŞTI:
     *
     *   modern → önizleme 14px, site 12px
     *   pill   → önizleme 24px, site 18px
     *
     * Yani "anlık önizleme" rozetiyle sunulan kutu, yanlış köşe gösteriyordu.
     * Yorum doğruyu söylüyordu, kod uymuyordu; artık uyuyor.
     *
     * `modern` bilerek Tailwind varsayılanlarına eşittir (dokunulmamış siteler
     * için no-op); diğerleri belirgin şekilde sapar. `rounded-full` etkilenmez.
     *
     * @return array{lg: string, xl: string, '2xl': string, '3xl': string}
     */
    public static function koseOlcegi(?string $anahtar): array
    {
        return match ($anahtar) {
            'sharp' => ['lg' => '2px', 'xl' => '3px', '2xl' => '4px', '3xl' => '6px'],
            'soft' => ['lg' => '6px', 'xl' => '8px', '2xl' => '10px', '3xl' => '14px'],
            'pill' => ['lg' => '14px', 'xl' => '18px', '2xl' => '24px', '3xl' => '32px'],
            default => ['lg' => '.5rem', 'xl' => '.75rem', '2xl' => '1rem', '3xl' => '1.5rem'],
        };
    }

    /**
     * Panel açılır menüsü için köşe seçenekleri.
     *
     * Etiketlerde PİKSEL YAZILMAZ. Eskiden "Modern (14px)" gibi yazıyordu ve
     * üç ayrı sorun taşıyordu: (1) 14px hiçbir katmanla eşleşmiyordu, (2) her
     * etiket başka bir katmandan (lg/xl/2xl) sayı almıştı, (3) ölçek değişince
     * etiket sessizce yalancı olurdu. Sayı vermeyen ad, yanlış sayı veren
     * addan iyidir.
     *
     * @return array<string, string>
     */
    public static function koseSecenekleri(): array
    {
        return [
            'sharp' => 'Keskin',
            'soft' => 'Yumuşak',
            'modern' => 'Modern (varsayılan)',
            'pill' => 'Kapsül',
        ];
    }

    /** Temaların iskelet dosyaları — bekçi testi bileşenin gerçekten basıldığını buradan doğrular. */
    public const ISKELETLER = [
        'klasik' => 'resources/views/components/layouts/app.blade.php',
        'vitrin' => 'resources/views/vitrin/components/layouts/app.blade.php',
    ];

    /** Bu anahtar verilen temada gerçekten bir şey yapıyor mu? */
    public static function gecerliMi(string $anahtar, string $tema): bool
    {
        return in_array($tema, self::JETONLAR[$anahtar]['temalar'] ?? [], true);
    }

    /**
     * Verilen temada geçerli olan anahtarlar.
     *
     * @return array<int, string>
     */
    public static function temaninJetonlari(string $tema): array
    {
        return array_keys(array_filter(
            self::JETONLAR,
            fn (array $j) => in_array($tema, $j['temalar'], true),
        ));
    }

    /**
     * Seçili Vitrin vurgu rampası. Geçersiz/boş değer varsayılana düşer —
     * tema motoru siteyi asla düşürmez disiplininin aynısı.
     *
     * @return array{label: string, hex: string, acik: array<int, string>, koyu: array<int, string>}
     */
    public static function vitrinAksani(?string $ad = null): array
    {
        /** @var array<string, array{label: string, hex: string, acik: array<int, string>, koyu: array<int, string>}> $rampalar */
        $rampalar = config('vitrin_accents', []);
        $ad ??= Settings::get('gorunum.vitrin_aksan', 'deniz');

        return $rampalar[$ad] ?? $rampalar['deniz'];
    }

    /** Geçerli Vitrin aksan adları. */
    public static function vitrinAksanAdlari(): array
    {
        return array_keys(config('vitrin_accents', []));
    }
}
