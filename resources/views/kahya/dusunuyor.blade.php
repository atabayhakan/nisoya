{{--
    "Kâhya düşünüyor" göstergesi — balon ve tam sayfa aynı parçayı kullanır
    (tek kopya ilkesi, bkz. KahyaSohbetiYurutur).

    wire:loading.flex ŞART: Livewire, loading öğesini gösterirken display'i
    varsayılan olarak inline-block yapar — class'taki flex ezilir, avatar ile
    balon alt alta yığılıp üst üste binmiş görünür (canlıda görüldü).

    Noktalar animate-bounce değil kendi eğrisiyle oynar: yalnız zıplamak yerine
    yükselirken büyüyüp parlar, inince söner — "yazıyor" hissi tek bakışta.
--}}
<style>
    @keyframes kahya-yaziyor {
        0%, 60%, 100% { transform: translateY(0) scale(1); opacity: 0.35; }
        30% { transform: translateY(-0.3rem) scale(1.25); opacity: 1; }
    }
</style>
<div wire:loading.flex wire:target="gonder" class="items-start gap-2.5">
    <x-kahya.avatar boyut="h-7 w-7 text-sm" class="mt-0.5" />
    <div class="flex items-center gap-1.5 rounded-2xl rounded-tl-md bg-gray-100 px-4 py-4 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/5">
        <span class="h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500" style="animation: kahya-yaziyor 1.2s ease-in-out infinite"></span>
        <span class="h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500" style="animation: kahya-yaziyor 1.2s ease-in-out 0.15s infinite"></span>
        <span class="h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500" style="animation: kahya-yaziyor 1.2s ease-in-out 0.3s infinite"></span>
    </div>
</div>
