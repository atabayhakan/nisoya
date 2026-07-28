{{--
    Site üstü duyuru bandı (Faz 2 · G8). Panelden yönetilir: Site Yönetimi →
    Duyuru Bandı. Kapalı veya metin boşsa hiç render edilmez. Metin düz metindir
    ({{ }} ile kaçışlanır — XSS yok). Kapatılabilirse ziyaretçi kapatınca aynı
    duyuruyu tekrar görmez (localStorage, mesaj değişince yeniden görünür).
--}}
@php
    $duyuruAktif = setting('duyuru.aktif', '0') === '1';
    $duyuruMetin = trim((string) setting('duyuru.metin', ''));
@endphp

@if ($duyuruAktif && $duyuruMetin !== '')
    @php
        $duyuruLink = trim((string) setting('duyuru.link', ''));
        $duyuruLinkMetni = trim((string) setting('duyuru.link_metni', ''));
        $duyuruRenk = setting('duyuru.renk', 'marka');
        $duyuruKapatilabilir = setting('duyuru.kapatilabilir', '1') === '1';

        $duyuruSiniflar = match ($duyuruRenk) {
            'uyari' => 'bg-amber-500 text-stone-950',
            'onemli' => 'bg-rose-600 text-white',
            default => 'bg-emerald-700 text-white',
        };

        // Mesaj/bağlantı/renk değişince kapatanlara tekrar göster.
        $duyuruAnahtar = 'nisoya-duyuru-'.substr(md5($duyuruMetin.$duyuruLink.$duyuruRenk), 0, 8);
    @endphp

    <div
        @if ($duyuruKapatilabilir)
            x-data="{ show: localStorage.getItem('{{ $duyuruAnahtar }}') !== '1' }"
            x-show="show"
            x-cloak
        @endif
        class="{{ $duyuruSiniflar }} text-sm"
        role="region"
        aria-label="Site duyurusu"
    >
        <div class="mx-auto flex max-w-6xl items-center justify-center gap-3 px-4 py-2 text-center">
            <span>
                {{ $duyuruMetin }}
                @if ($duyuruLink !== '')
                    <a href="{{ $duyuruLink }}" class="font-semibold underline underline-offset-2 hover:no-underline">
                        {{ $duyuruLinkMetni !== '' ? $duyuruLinkMetni : 'Detay →' }}
                    </a>
                @endif
            </span>

            @if ($duyuruKapatilabilir)
                <button
                    type="button"
                    x-on:click="show = false; localStorage.setItem('{{ $duyuruAnahtar }}', '1')"
                    class="shrink-0 rounded p-1 opacity-80 transition hover:opacity-100"
                    aria-label="Duyuruyu kapat"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@endif
