<div class="space-y-4 text-xs">
    {{-- İşletme ve Bağlantı Başlığı --}}
    <div class="p-3 rounded-xl border border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <span class="font-bold text-sm text-gray-950 dark:text-white">{{ $aday->name }}</span>
            <p class="text-gray-700 dark:text-gray-300 mt-0.5">
                Konum: {{ $aday->city }} · {{ $aday->country }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-700 text-white font-semibold hover:bg-emerald-800 transition">
                <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="h-4 w-4" />
                <span>WhatsApp'ta Aç</span>
            </a>
        </div>
    </div>

    {{-- Kişisel Dokunuş --}}
    <div class="p-3 rounded-lg border border-primary-200 bg-primary-50/60 dark:border-primary-900/50 dark:bg-primary-950/20">
        <span class="font-semibold text-primary-950 dark:text-primary-300">Özel Samimi Dokunuş:</span>
        <p class="text-gray-800 dark:text-gray-200 mt-0.5">
            "{{ $davet['personal_touch'] }}"
        </p>
    </div>

    {{-- WhatsApp Önizleme Kutusu --}}
    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <span class="font-semibold text-gray-950 dark:text-white flex items-center gap-1">
                <span class="h-2 w-2 rounded-full bg-emerald-700"></span>
                WhatsApp Mesaj Taslağı:
            </span>
            <span class="text-[11px] text-gray-500">15 saniye tek tıkla sahiplenme linki içerir</span>
        </div>
        <div class="rounded-xl bg-[#0b141a] p-4 text-gray-100 font-sans shadow-inner space-y-2 whitespace-pre-wrap leading-relaxed select-all">
{{ $davet['whatsapp_message'] }}
        </div>
    </div>

    {{-- E-posta Alternatifi --}}
    <div class="p-3 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 space-y-1.5">
        <div class="font-semibold text-gray-950 dark:text-white">
            E-posta Konusu: <span class="font-normal text-gray-800 dark:text-gray-200">{{ $davet['email_subject'] }}</span>
        </div>
        <div class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono text-[11px] p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 max-h-40 overflow-y-auto">
{{ $davet['email_body'] }}
        </div>
    </div>
</div>
