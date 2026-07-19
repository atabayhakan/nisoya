{{-- Ödeme anı güvenlik kartı (K-A). Bir satıcının ödeme bilgisi/linki
     gösterilen her yere yerleştirilir. Nisoya ödemeye aracılık etmediğinden
     alıcının korunması, riskin tam bu noktada net biçimde hatırlatılmasına
     dayanır. Metnin sertliği satıcının güven kademesine göre artar.
     Değişken: $seller (App\Models\User). --}}
@php $sellerIsNew = $seller->isNewSeller(); @endphp

<div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900 dark:border-amber-500/30 dark:bg-amber-900/20 dark:text-amber-200">
    <p class="font-semibold"><span aria-hidden="true">⚠️</span> Güvenli ödeme ipuçları</p>
    <p class="mt-1">
        Nisoya ödemeye <strong>aracılık etmez</strong>; ödeme ve anlaşma tamamen seninle satıcı arasındadır. Peşin ödemenin riski sana aittir.
    </p>
    <ul class="mt-1.5 list-disc space-y-0.5 pl-4">
        <li>PayPal kullanacaksan mutlaka <strong>&ldquo;Mal ve Hizmetler&rdquo;</strong> seç — <strong>&ldquo;Arkadaş/Aile&rdquo; ile gönderilen para geri alınamaz</strong>.</li>
        <li>Tanımadığın satıcıya tutarın tamamını peşin gönderme; mümkünse teslimde ya da yüz yüze öde.</li>
        <li>Piyasa altı fiyat, acele baskısı ve &ldquo;önce kapora&rdquo; isteklerine şüpheyle yaklaş.</li>
    </ul>
    @if ($sellerIsNew)
        <p class="mt-2 rounded-lg bg-red-100 px-2 py-1.5 font-medium text-red-800 dark:bg-red-900/40 dark:text-red-200">
            <span aria-hidden="true">🔺</span> Bu satıcı platforma yeni katılmış ve henüz değerlendirilmemiş. Ödemede ekstra dikkatli ol.
        </p>
    @endif
</div>
