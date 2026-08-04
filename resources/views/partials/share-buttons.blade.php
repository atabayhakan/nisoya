{{-- $shareUrl ve $shareText bekler; $cardUrl (paylaşım kartı PNG'si) isteğe bağlı --}}
<div class="flex flex-wrap items-center gap-2">
    <a href="https://wa.me/?text={{ urlencode($shareText.' — '.$shareUrl) }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1.5 rounded-lg bg-[#25D366] px-3 py-2 text-sm font-medium text-white transition hover:opacity-90">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 0 1 8.413 3.488 11.82 11.82 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.51 5.26l-.999 3.648 3.978-1.607zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
        WhatsApp
    </a>
    @if (! empty($cardUrl))
        {{-- Script @if İÇİNDE: yalnız kart butonu basılan sayfalara insin
             (profil/şirket/iş ilanı bu partial'ı kartsız kullanıyor).
             @stack bu depoda TANIMLI DEĞİL — @push('scripts') sessizce hiçbir
             yere basılmaz (cookie-consent'teki @push('head') de aynı sebeple
             ölü), o yüzden script satır içi. @once tekrarını engelliyor. --}}
        @once
            <script>
                // WhatsApp DURUMU için kart paylaşımı. Sıra bilinçli:
                // 1) Web Share API dosyayla — kullanıcı doğrudan "Durum"u seçebilir.
                //    Yalnız mobil tarayıcıların bir kısmı destekler ve HTTPS ister.
                // 2) Desteklenmiyorsa yeni sekmede aç — kullanıcı görseli kaydedip
                //    kendi durumuna koyar. İndirme zorlamak yerine açmak tercih
                //    edildi: iOS'ta indirilen dosyayı galeriye almak fazladan adım.
                window.nisoyaKartPaylas = async function (btn, kartUrl, baslik) {
                    const etiket = btn.querySelector('span');
                    const eski = etiket.textContent;
                    etiket.textContent = 'Hazırlanıyor…';
                    btn.disabled = true;

                    try {
                        const yanit = await fetch(kartUrl);
                        if (!yanit.ok) throw new Error('kart alınamadı');
                        const blob = await yanit.blob();
                        const dosya = new File([blob], 'nisoya-ilan.png', { type: 'image/png' });

                        if (navigator.canShare && navigator.canShare({ files: [dosya] })) {
                            await navigator.share({ files: [dosya], title: baslik });
                            return;
                        }

                        window.open(kartUrl, '_blank', 'noopener');
                    } catch (e) {
                        // AbortError = kullanıcı paylaşım sayfasını kapattı; hata değil.
                        if (e && e.name !== 'AbortError') window.open(kartUrl, '_blank', 'noopener');
                    } finally {
                        etiket.textContent = eski;
                        btn.disabled = false;
                    }
                };
            </script>
        @endonce

        <button type="button"
                onclick="nisoyaKartPaylas(this, @js($cardUrl), @js($shareText))"
                class="inline-flex items-center gap-1.5 rounded-lg bg-stone-800 px-3 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:opacity-60 dark:bg-stone-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/><path d="m21 15-5-5L5 21"/>
            </svg>
            <span>Durumuma koy</span>
        </button>
    @endif

    <button type="button"
            onclick="navigator.clipboard.writeText('{{ $shareUrl }}').then(() => { const t = this.querySelector('span'); const o = t.textContent; t.textContent = '✓ Kopyalandı'; setTimeout(() => t.textContent = o, 2000); })"
            class="inline-flex items-center gap-1.5 rounded-lg border border-stone-300 px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-400">
        <span>Bağlantıyı kopyala</span>
    </button>
</div>
