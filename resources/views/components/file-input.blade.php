@props([
    'name',
    'id' => null,
    'label',
    'hint' => null,
    'accept' => 'image/*',
])

@php
    $inputId = $id ?? $name;
@endphp

{{-- Yerel dosya girdisi yerine tek bir görünüm.

     Neden: `input[type=file]`in düğme metnini ("Choose File") ve boş durum
     metnini ("No file chosen") CSS ile değiştirmek MÜMKÜN DEĞİL — tarayıcı
     bunları kendi dilinde basar. Sonuç, Türkçe bir panelde İngilizce iki
     etiketti (ekran görüntüsüyle bildirildi). `file:` yardımcı sınıfları
     yalnız düğmeyi boyar, metni değiştiremez.

     Çözüm: gerçek girdi görünmez kalır (erişilebilirlik için display:none
     DEĞİL, sr-only — klavye ve ekran okuyucu erişimi sürer), etiketi biz
     basarız ve seçilen dosyanın adını Alpine ile gösteririz. --}}
<div x-data="{ dosya: '' }">
    <label for="{{ $inputId }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ $label }}</label>

    <div class="mt-1 flex items-center gap-3">
        <label
            for="{{ $inputId }}"
            class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 focus-within:ring-2 focus-within:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-stone-700"
        >
            <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
            Dosya seç
        </label>

        <span class="min-w-0 flex-1 truncate text-sm text-stone-500 dark:text-stone-400"
              x-text="dosya || 'Henüz dosya seçilmedi'"></span>
    </div>

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="file"
        accept="{{ $accept }}"
        class="sr-only"
        @change="dosya = $event.target.files[0]?.name ?? ''"
        {{ $attributes }}
    >

    @if ($hint)
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $hint }}</p>
    @endif

    @error($name) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
