{{-- Ödeme anı güvenlik kartı (K-A). Bir satıcının ödeme bilgisi/linki
     gösterilen her yere yerleştirilir. Nisoya ödemeye aracılık etmediğinden
     alıcının korunması, riskin tam bu noktada net biçimde hatırlatılmasına
     dayanır. Metnin sertliği satıcının güven kademesine göre artar.
     Değişken: $seller (App\Models\User). --}}
@php $sellerIsNew = $seller->isNewSeller(); @endphp

<div class="mt-4 rounded-2xl border border-amber-200/80 bg-amber-50/60 p-4 text-xs leading-relaxed text-amber-900 dark:border-amber-500/30 dark:bg-amber-900/20 dark:text-amber-200">
    <div class="flex items-center gap-1.5 font-bold text-amber-900 dark:text-amber-100">
        <x-heroicon-s-shield-check class="h-4 w-4 text-amber-600 dark:text-amber-400" />
        <span>Güvenli Alışveriş & Ödeme İpuçları</span>
    </div>
    <p class="mt-1.5 text-stone-600 dark:text-stone-300">
        Nisoya ödemeye <strong>aracılık etmez</strong>; ödeme ve anlaşma tamamen seninle satıcı arasındadır. Peşin ödemenin riski alıcıya aittir.
    </p>
    <ul class="mt-2 list-disc space-y-1 pl-4 text-stone-600 dark:text-stone-300">
        <li>PayPal kullanacaksan mutlaka <strong>&ldquo;Mal ve Hizmetler&rdquo;</strong> seç — <strong>&ldquo;Arkadaş/Aile&rdquo; ile gönderilen para geri alınamaz</strong>.</li>
        <li>Tanımadığın satıcıya tutarın tamamını peşin gönderme; mümkünse teslimde ya da yüz yüze öde.</li>
        <li>Piyasa altı fiyat, acele baskısı ve &ldquo;önce kapora&rdquo; isteklerine şüpheyle yaklaş.</li>
    </ul>
    @if ($sellerIsNew)
        <div class="mt-2.5 flex items-center gap-1.5 rounded-xl border border-amber-300/80 bg-amber-100/80 px-3 py-2 font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
            <span aria-hidden="true">🔺</span>
            <span>Bu satıcı platforma yeni katılmış ve henüz değerlendirilmemiş. Ödemede ekstra dikkatli ol.</span>
        </div>
    @endif
</div>
