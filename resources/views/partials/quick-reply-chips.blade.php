{{--
    Hızlı cevap çipleri — ilan detayındaki mesaj kutusunun ÜSTÜNE konur.

    Neden: iletişim kutusu boş bir `required` textarea. İlk teması kuracak
    kişi "ne yazsam" diye takılıyor ve hiç yazmıyor. Çipler cümleyi metin
    kutusuna DÜŞÜRÜR, göndermez — kullanıcı düzenleyip kendi gönderir.

    Sözleşme:
      - Bu partial mesaj formunun İÇİNE, textarea'dan önce konulmalı;
        script en yakın form'daki `textarea[name="body"]`'yi bulur.
      - Klasik ve Vitrin şablonlarının İKİSİ de bunu include eder
        (vitrin/ altındaki dosyalar klasiği geçersiz kılar; tek kopya
        tutulmazsa biri sessizce geride kalır).
      - Klasik şablonda Alpine yok, o yüzden düz JS.

    Beklenen değişken: $listing
--}}
@php $hizliMesajlar = $listing->type->hizliMesajlar(); @endphp

@if ($hizliMesajlar)
    {{-- JS kapalıyken çipler hiçbir şey yapamaz; o yüzden gizli başlar ve
         script açar (aşamalı iyileştirme). --}}
    <div class="hidden" data-quick-reply-group>
        <p class="mb-1.5 text-xs font-medium text-stone-500 dark:text-stone-400">Hızlı başlangıç</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($hizliMesajlar as $mesaj)
                <button type="button"
                        data-quick-reply="{{ $mesaj }}"
                        class="rounded-full bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-600 transition hover:bg-emerald-100 hover:text-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300">
                    {{ $mesaj }}
                </button>
            @endforeach
        </div>
    </div>

    @once
        <script>
            (function () {
                document.querySelectorAll('[data-quick-reply-group]').forEach(function (group) {
                    group.classList.remove('hidden');
                });

                document.addEventListener('click', function (event) {
                    var chip = event.target.closest('[data-quick-reply]');
                    if (! chip) {
                        return;
                    }

                    var form = chip.closest('form');
                    var alan = form && form.querySelector('textarea[name="body"]');
                    if (! alan) {
                        return;
                    }

                    // Yazılmış metni ASLA silme — doluysa alt satıra ekle.
                    var mevcut = alan.value.trim();
                    alan.value = mevcut ? mevcut + '\n' + chip.dataset.quickReply : chip.dataset.quickReply;

                    alan.focus();
                    alan.setSelectionRange(alan.value.length, alan.value.length);
                    alan.dispatchEvent(new Event('input', { bubbles: true }));
                });
            })();
        </script>
    @endonce
@endif
