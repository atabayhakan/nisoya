<x-layouts.app title="Davetiyeyi Düzenle — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-panel.back-link :href="route('panel.events.show', $event)" label="Davetiye yönetimi" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">Davetiyeyi Düzenle</h1>

        <form method="POST" action="{{ route('panel.events.update', $event) }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            @include('panel.events.partials.form-fields', ['event' => $event])

            <div class="space-y-2 rounded-xl border border-stone-200 p-4 dark:border-stone-800">
                <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $event->is_active))
                           class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                    Davet linki açık (kapatırsan misafirler sayfayı göremez)
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                    <input type="hidden" name="allow_uploads" value="0">
                    <input type="checkbox" name="allow_uploads" value="1" @checked(old('allow_uploads', $event->allow_uploads))
                           class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                    Anı akışı açık (etkinlik gününden itibaren misafirler fotoğraf/video paylaşabilir)
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                    <input type="hidden" name="require_approval" value="0">
                    <input type="checkbox" name="require_approval" value="1" @checked(old('require_approval', $event->require_approval))
                           class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                    Paylaşımlar önce onayımdan geçsin (kalabalık etkinliklerde önerilir)
                </label>
                <label class="flex items-start gap-2 text-sm text-stone-700 dark:text-stone-300">
                    <input type="hidden" name="album_is_public" value="0">
                    <input type="checkbox" name="album_is_public" value="1" @checked(old('album_is_public', $event->album_is_public))
                           class="mt-0.5 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600 dark:bg-stone-800">
                    <span>
                        Albümü herkese aç — <a href="{{ route('happy-moments') }}" target="_blank" class="text-emerald-700 underline-offset-2 hover:underline dark:text-emerald-400">Mutlu Anlar</a> vitrininde yer alır.
                        <span class="block text-xs text-stone-400 dark:text-stone-500">Açmadan önce fotoğraflardaki kişilerin rızası olduğundan emin ol — sorumluluk etkinlik sahibine aittir.</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                    Değişiklikleri Kaydet
                </button>
                <a href="{{ $event->inviteUrl() }}" target="_blank" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Davetiyeyi gör →</a>
            </div>
        </form>

        <form method="POST" action="{{ route('panel.events.destroy', $event) }}" class="mt-8 border-t border-stone-200 pt-6 dark:border-stone-800"
              onsubmit="return confirm('Bu etkinliği ve tüm davetli listesini silmek istediğine emin misin? Bu işlem geri alınamaz.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">Etkinliği sil</button>
        </form>
    </div>
</x-layouts.app>
