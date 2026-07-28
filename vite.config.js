import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            fonts: [
                // ------------------------------------------------------------
                // PRELOAD SEÇİCİDİR, "hepsi" DEĞİL
                //
                // Varsayılan `preload: true` her ağırlığın her alt kümesini
                // işaretliyordu: üç aile için 32 preload. Preload yüksek
                // öncelikli bir indirmedir; o kadarı sayfanın kendi kritik
                // kaynaklarıyla yarışır. Artık yalnız ilk boyamada kesinlikle
                // görünen ağırlıklar preload edilir (gövde + teşhir kalını);
                // kalan ağırlıklar zaten @font-face ile tanımlı ve
                // font-display:swap sayesinde ihtiyaç anında gelir.
                // Ölçüldü: klasik sayfa 4 dosya / 16 KB, Vitrin 8 / 32 KB.
                //
                // ALT KÜME NOTU: `subsets` seçeneği bilerek yazılmadı. Bunny
                // sağlayıcısı istenen alt kümeden bağımsız olarak latin,
                // latin-ext, vietnamese ve cyrillic-ext yüzlerini üretiyor —
                // denendi, çıktı değişmedi; etkisiz bir seçenek bırakmak
                // ilerideki okuyucuyu yanıltırdı. Maliyeti de yok: her yüz
                // ~4 KB ve unicode-range sayesinde tarayıcı yalnız sayfada
                // geçen harflerin dosyasını indirir.
                //
                // TÜRKÇE İÇİN KRİTİK OLAN NOKTA: ğ (U+011F), ş (U+015F) ve
                // İ (U+0130) `latin` alt kümesinde DEĞİL, `latin-ext`tedir.
                // Alt kümeleri daraltmaya kalkışan biri `latin` ile yetinirse
                // Türkçe metin aynı satır içinde harf harf sistem fontuna
                // düşer. latin-ext hiçbir koşulda çıkarılmamalı.
                // ------------------------------------------------------------

                // 700 GEREKLİ, süs değil: klasik temanın yazı tipi bu ve
                // sitede 155 yerde `font-bold` (700) kullanılıyor. 700
                // yüklenmediği için tarayıcı 600'ü yatay şişirerek SAHTE
                // kalın üretiyordu — harf kenarları bulanık, aralıklar
                // bozuk. Ana sayfanın H1'i dahil. Tek satır, 155 yer.
                bunny('Instrument Sans', {
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400 }, { weight: 700 }],
                }),
                // Tek bir dekoratif başlıkta kullanılıyor ("Nisoya'nın Nabzı")
                // ve 2. Tasarım modunun hero'sunda. Preload edilmez.
                bunny('Instrument Serif', {
                    weights: [400],
                    styles: ['normal', 'italic'],
                    preload: false,
                }),
                // Vitrin temasının yazı tipi (P1) — self-host, Google'a istek yok.
                // Vitrin gövdesi 400, teşhir başlıkları 800 (45 yerde, gerçek).
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700, 800],
                    preload: [{ weight: 400 }, { weight: 800 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
