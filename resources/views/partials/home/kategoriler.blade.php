    @if (\App\Support\HomeSections::visible('kategoriler'))
    {{-- KATEGORİLER: KART DUVARI → ÇİP ŞERİDİ (2026-08-08)

         ÖLÇÜLDÜ (1280px, canlı DOM): bu bölüm 831px ile sayfanın EN BÜYÜK
         yüzeyiydi — hero'dan (603px) bile büyük. 21 kategori kartı, beşi bir
         satırda, hepsi piksel piksel aynı genişlikte (211.19px).

         Aynı anda ölçülen ikinci gerçek: 21 kategorinin 13'ünde HİÇ ilan yok,
         kalan 8'in 7'sinde yalnız `[ÖRNEK]` demo ilan var. Gerçek arzı olan
         kategori sayısı: 1. Yani sayfanın en geniş yüzeyi 21 ölü uçtu.

         İKİ TASARIMCININ UZLAŞTIĞI YER — biri "bölümü büdayalım" dedi, diğeri
         "hayır, kategori bir GEZİNME ögesi, envanter vaadi değil; envantere
         göre budamak envanteri hâlâ merkeze almaktır" diye itiraz etti.
         İkisi de haklıydı, ama farklı şey hakkında:

           · Gezinme işi ("Nisoya ne işe yarar") arz GEREKTİRMEZ → kalmalı
           · Vitrin işi ("bu kategoride mal var, tıkla") arz GEREKTİRİR → bugün
             yapılamıyor, kaldırılmalı

         Kart o ikisini birbirine karıştırıyordu: kart "içeride bir şey var"
         der. Çip "buraya gidebilirsin" der. Kategori bugün ikincisi.

         SAYI BASILMIYOR — bilerek. Basılsaydı 20 kategoride "0" yazardı;
         `is_demo` süzgeci olmadan basılsaydı daha kötüsü olur, "Eğitim & Ders 6"
         yazıp altısı da demo olurdu (bkz. HomeController'ın `heroCips` deseni).
         Sayı, gerçek arz geldiğinde eklenecek bir sonraki adım.

         Ülke hapıyla AYNI dil kullanılıyor (yuvarlak çip, aynı kenar/gölge) —
         iki bölüm de aynı işi yapıyor: gezinme. Farklı görünmeleri için sebep
         yoktu. --}}
    <section class="mx-auto max-w-6xl px-4 py-14" x-data x-reveal>
        <div class="flex items-end justify-between">
            <h2 class="text-3xl font-serif font-normal text-stone-900 md:text-4xl dark:text-stone-50">Kategoriler</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</a>
        </div>
        <div class="mt-5 flex flex-wrap gap-2">
            @foreach ($categories as $cat)
                <a href="{{ route('listings.category', $cat->slug) }}"
                   class="group inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700 hover:shadow-brand dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200 dark:shadow-none dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                    <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($cat->icon)" class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                    <span>{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif
