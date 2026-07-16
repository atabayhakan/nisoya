<x-layouts.app title="Nasıl Çalışır? — Nisoya">
    @php
        // Tek kaynak: hem görünür SSS bölümü hem FAQPage JSON-LD buradan üretilir.
        $faqs = [
            ['Nisoya ücretsiz mi?', 'Evet. İlan vermek de kullanmak da tamamen ücretsizdir. Nisoya gelirini reklam ve gönüllü bağışlarla karşılar; senden komisyon veya üyelik ücreti almaz.'],
            ['Ödemeyi Nisoya mı alıyor?', 'Hayır. Nisoya bir ilan ve iletişim platformudur; ödemeye aracılık etmez. Anlaştığın kişiyle ödeme yöntemini kendiniz belirlersiniz (banka havalesi, PayPal, nakit vb.).'],
            ['Nisoya kimler için?', 'Yurt dışında yaşayan Türkler için. Hizmet, ürün, emlak, vasıta ilanı verebilir; iş ilanı paylaşabilir veya kendi dilinde güvenle hizmet/ürün arayabilirsin.'],
            ['İlan vermek için ne gerekiyor?', 'Ücretsiz bir hesap açıp e-postanı doğrulaman yeterli. Ardından "İlan Ver" ile başlık, açıklama, fiyat, konum ve görsel ekleyebilirsin.'],
            ['Dolandırıcılıktan nasıl korunurum?', 'İş bitmeden tüm ücreti peşin ödemekten kaçın, ilk kez çalıştığın kişiyle büyük tutarlarda temkinli ol, elden teslimde güvenli/halka açık yerde buluş. Şüpheli ilan veya kullanıcıyı "şikayet et" ile bize bildir.'],
            ['Hangi ülkelerde kullanılıyor?', 'Belirli bir ülkeye kilitli değil — Avrupa, ABD, Körfez ülkeleri ve Türk dünyası dahil dünya genelindeki Türkler kullanabilir.'],
        ];
    @endphp

    {{-- JSON-LD: FAQPage (Google zengin sonuçlar + AI asistanları için) --}}
    <x-json-ld type="FAQPage" :data="[
        'mainEntity' => collect($faqs)->map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
        ])->all(),
    ]" />

    <div class="mx-auto max-w-4xl px-4 py-12">
        <h1 class="text-3xl font-bold text-stone-900">Nasıl çalışır?</h1>
        <p class="mt-2 text-stone-600">Nisoya, yurt dışındaki Türkleri buluşturan bir ilan ve iletişim platformudur. Ödeme ve anlaşma her zaman taraflar arasındadır.</p>

        <div class="mt-10 grid gap-8 md:grid-cols-2">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-emerald-700">🙋 Hizmet/ürün sunuyorsan</h2>
                <ol class="mt-4 space-y-3 text-sm text-stone-600">
                    <li><strong>1.</strong> Ücretsiz kayıt ol, e-postanı doğrula.</li>
                    <li><strong>2.</strong> "İlan Ver" ile yeteneğini/hizmetini anlat; fiyat, konum ve görsel ekle.</li>
                    <li><strong>3.</strong> Gelen mesajlara cevap ver, müşterilerinle anlaş.</li>
                </ol>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-emerald-700">🔍 Hizmet/ürün arıyorsan</h2>
                <ol class="mt-4 space-y-3 text-sm text-stone-600">
                    <li><strong>1.</strong> İlanları ara, ülke/şehir ve kategoriye göre filtrele.</li>
                    <li><strong>2.</strong> Beğendiğin ilanı incele, satıcının değerlendirmelerine bak.</li>
                    <li><strong>3.</strong> Satıcıya mesaj gönder, güvenle anlaş.</li>
                </ol>
            </div>
        </div>

        <div class="mt-10 rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="text-lg font-bold text-amber-900">💳 Ödeme nasıl yapılır?</h2>
            <p class="mt-2 text-sm text-amber-800">
                Nisoya bir ilan ve iletişim platformudur — ödemeyi biz almıyor, aracılık etmiyoruz. Anlaştığın kişiyle ödeme yöntemini kendin belirlersin. Üye profillerinde "Kabul ettiği ödeme yöntemleri" rozetlerini görebilirsin (IBAN/banka havalesi, Kaspi, Click, Payme, MBANK, Zelle, Venmo, PayPal, nakit gibi — bulunduğun ülkeye göre değişir).
            </p>
            <ul class="mt-3 space-y-1.5 text-sm text-amber-800">
                <li>• Hizmet tamamlanmadan tüm ücreti peşin ödemekten kaçın; mümkünse iş bitince veya kısım kısım öde.</li>
                <li>• İlk defa çalıştığın biriyle büyük tutarlarda temkinli ol, önce küçük bir işle güven oluştur.</li>
                <li>• Elden teslimde, mümkünse halka açık/güvenli bir yerde buluş.</li>
                <li>• Şüpheli bir durum sezersen ilanı veya kullanıcıyı "şikayet et" ile bize bildir.</li>
            </ul>
        </div>

        {{-- Sıkça Sorulan Sorular (görünür + FAQPage JSON-LD ile aynı kaynak) --}}
        <div class="mt-10">
            <h2 class="text-xl font-bold text-stone-900">Sıkça sorulan sorular</h2>
            <div class="mt-4 divide-y divide-stone-200 overflow-hidden rounded-2xl border border-stone-200 bg-white">
                @foreach ($faqs as [$soru, $cevap])
                    <details class="group">
                        <summary class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 text-sm font-semibold text-stone-800 marker:content-none hover:bg-stone-50">
                            {{ $soru }}
                            <svg class="h-5 w-5 shrink-0 text-stone-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </summary>
                        <p class="px-5 pb-4 text-sm leading-relaxed text-stone-600">{{ $cevap }}</p>
                    </details>
                @endforeach
            </div>
        </div>

        <div class="mt-8 rounded-2xl bg-emerald-50 p-6 text-center">
            <h2 class="text-lg font-bold text-stone-900">Güven önce gelir</h2>
            <p class="mt-2 text-sm text-stone-600">Profil doğrulaması, değerlendirme/puan ve şikayet sistemiyle topluluğu güvende tutuyoruz. Şüpheli bir ilan görürsen "şikayet et" ile bize bildir.</p>
            <a href="{{ route('register') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-6 py-2.5 font-semibold text-white hover:bg-emerald-700">Ücretsiz Başla</a>
        </div>
    </div>
</x-layouts.app>
