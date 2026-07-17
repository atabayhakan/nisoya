{{-- Şifre alanı + göster/gizle butonu (mobilde küçük klavyede yazım hatasını
     görebilmek için istendi, ama tüm cihazlarda faydalı). Statik
     type="password" JS yüklenmeden önce varsayılan maskeli görünümü garanti
     eder; Alpine :type bunun üzerine reaktif olarak biner. --}}
<div class="relative" x-data="{ show: false }">
    <input type="password" :type="show ? 'text' : 'password'" {{ $attributes }}>
    <button
        type="button"
        tabindex="-1"
        @click="show = !show"
        :aria-label="show ? 'Şifreyi gizle' : 'Şifreyi göster'"
        class="absolute inset-y-0 right-2 flex items-center text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300"
    >
        <x-heroicon-o-eye x-show="!show" class="h-4 w-4" />
        <x-heroicon-o-eye-slash x-show="show" x-cloak class="h-4 w-4" />
    </button>
</div>
