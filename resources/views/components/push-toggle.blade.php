{{-- Web push aç/kapa düğmesi (Faz M1.3). Push desteklenmiyorsa (örn. iOS'ta
     site ana ekrana eklenmeden açıldıysa) hiç görünmez — x-cloak + x-show.
     JS tarafı: resources/js/app.js → pushToggle(). --}}
@if (config('webpush.vapid.public_key'))
    <div
        x-data="pushToggle(@js(config('webpush.vapid.public_key')), @js(route('push.subscribe')), @js(route('push.unsubscribe')))"
        x-show="supported"
        x-cloak
    >
        <button
            type="button"
            @click="toggle()"
            :disabled="busy || denied"
            :title="denied ? 'Bildirim izni tarayıcıdan engellenmiş — site ayarlarından izin verebilirsin' : (subscribed ? 'Anlık bildirimleri kapat' : 'Yeni mesajlarda anlık bildirim al')"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
            :class="subscribed
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800'"
        >
            <template x-if="subscribed">
                <x-heroicon-s-bell-alert class="h-4 w-4" />
            </template>
            <template x-if="!subscribed">
                <x-heroicon-o-bell class="h-4 w-4" />
            </template>
            <span x-text="denied ? 'Bildirimler engelli' : (subscribed ? 'Bildirimler açık' : 'Bildirim al')"></span>
        </button>
    </div>
@endif
