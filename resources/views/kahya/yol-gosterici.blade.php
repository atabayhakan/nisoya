{{--
    Kâhya yol göstericisi — "X nerede?" cevabında hedef ekranın sol menüdeki
    ögesini birkaç saniye yakıp söndürür (kahya-yonlendir olayı,
    bkz. App\Livewire\Concerns\KahyaSohbetiYurutur::gonder).

    Sayfa KENDİLİĞİNDEN DEĞİŞTİRİLMEZ. Gerekçe: yanlış anlaşılmış bir soru
    sahibi istemediği bir ekrana ışınlamamalı; vurgu yanılırsa zararsızdır,
    otomatik gidiş yanılırsa iş kesintisidir. Üstelik "nerede?" sorusunun
    amacı ÖĞRENMEK — menüde yanan öge yeri öğretir, ışınlanma öğretmez.
    Gitmek isteyen için cevaptaki "Aç" düğmesi tek tık zaten
    (bkz. kahya/mesajlar.blade.php).

    AdminPanelProvider'ın BODY_END render hook'uyla panelin her sayfasına
    girer — balon nerede konuşabiliyorsa vurgu orada çalışır.
--}}
<style>
    @keyframes kahya-vurgu-atis {
        0%, 100% { background-color: transparent; box-shadow: none; }
        50% {
            background-color: color-mix(in srgb, var(--primary-500) 18%, transparent);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary-500) 45%, transparent);
        }
    }

    .kahya-vurgu {
        animation: kahya-vurgu-atis 1.1s ease-in-out 4;
        border-radius: 0.5rem;
    }
</style>
<script>
    window.addEventListener('kahya-yonlendir', (olay) => {
        const url = olay.detail?.url;
        if (! url) return;

        let yol;
        try {
            yol = new URL(url, window.location.origin).pathname.replace(/\/+$/, '');
        } catch { return; }

        const baglanti = [...document.querySelectorAll('.fi-sidebar-item > a[href]')].find((a) => {
            try { return new URL(a.href).pathname.replace(/\/+$/, '') === yol; } catch { return false; }
        });
        if (! baglanti) return; // Menüde yoksa (ör. mobil/dar ekran) düğme yeter.

        // Kapalı bir grubun içindeyse önce grubu aç — görünmeyen vurgu, vurgu değildir.
        if (baglanti.offsetParent === null) {
            baglanti.closest('.fi-sidebar-group')?.querySelector('button')?.click();
        }

        const oge = baglanti.closest('.fi-sidebar-item') ?? baglanti;

        // Grubun açılma animasyonu bitmeden scrollIntoView yanlış yere kayar.
        setTimeout(() => {
            oge.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            oge.classList.add('kahya-vurgu');
            setTimeout(() => oge.classList.remove('kahya-vurgu'), 4600);
        }, 100);
    });
</script>
