{{-- Tek mesaj balonu — ilk (sunucu) render'ı. JS poll tarafı aynı yapıyı
     resources/views/panel/messages/show.blade.php içindeki appendMessage() ile
     kurar; ikisi senkron kalmalı. --}}
<div class="msg flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-id="{{ $message->id }}" data-mine="{{ $mine ? '1' : '0' }}">
    @if ($message->isRecalled())
        <div class="max-w-[75%] rounded-2xl px-4 py-2 text-sm italic text-stone-600 ring-1 ring-stone-200 dark:text-stone-400 dark:ring-stone-700">
            <span class="select-none">🚫</span> Bu mesaj geri alındı
        </div>
    @else
        <div class="max-w-[75%] rounded-2xl px-4 py-2 {{ $mine ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' : 'bg-white text-stone-800 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-100 dark:ring-stone-700' }}">
            @if ($message->isImage() && $message->imageUrl())
                <a href="{{ $message->imageUrl() }}" target="_blank" rel="noopener">
                    <img src="{{ $message->imageUrl() }}" loading="lazy" class="max-h-64 w-auto rounded-lg" alt="Paylaşılan fotoğraf">
                </a>
                @if ($message->body)
                    <p class="body mt-1 whitespace-pre-line break-words text-sm">{{ $message->body }}</p>
                @endif
            @elseif ($message->isLocation() && $message->coords())
                @php($c = $message->coords())
                <a href="https://www.openstreetmap.org/?mlat={{ $c['lat'] }}&mlon={{ $c['lng'] }}#map=16/{{ $c['lat'] }}/{{ $c['lng'] }}"
                   target="_blank" rel="noopener"
                   class="flex items-center gap-2 text-sm font-medium {{ $mine ? 'text-white' : 'text-emerald-700 dark:text-emerald-400' }}">
                    <span class="select-none">📍</span> Konumu haritada aç
                </a>
            @else
                <p class="body whitespace-pre-line break-words text-sm">{{ $message->body }}</p>
            @endif

            <p class="meta mt-1 flex items-center justify-end gap-1.5 text-2xs {{ $mine ? 'text-emerald-100' : 'text-stone-600 dark:text-stone-400' }}">
                <span>{{ $message->created_at->format('d.m H:i') }}</span>
                @if ($mine && $message->read_at)<span class="read-mark">· okundu</span>@endif
                @if ($mine)<button type="button" class="recall-btn font-medium underline decoration-dotted underline-offset-2 hover:no-underline">· geri al</button>@endif
            </p>

            {{-- Dikkat notu — yalnız KARŞI TARAFIN mesajında (uyarının muhatabı
                 alıcı). Engellemez, işaretler.

                 METİN BURADA KURULMUYOR: App\Support\SohbetUyarisi tek kaynak.
                 Eskiden aynı cümle hem burada hem JS aynasında yazılıydı ve
                 iki dosyanın başında "birebir aynı kalmalı" uyarısı vardı —
                 ikinci bir uyarı türü eklerken bu dörde çıkardı. --}}
            @php($uyari = $mine ? null : \App\Support\SohbetUyarisi::bul($message->body))
            @if ($uyari)
                <p class="platform-uyari mt-1.5 border-t border-amber-300/50 pt-1.5 text-2xs font-medium text-amber-700 dark:border-amber-400/20 dark:text-amber-400">
                    {{ $uyari['metin'] }}
                </p>
            @endif
        </div>
    @endif
</div>
