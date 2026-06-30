<x-layouts.app title="Nasıl Çalışır? — Nisoya">
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

        <div class="mt-10 rounded-2xl bg-emerald-50 p-6 text-center">
            <h2 class="text-lg font-bold text-stone-900">Güven önce gelir</h2>
            <p class="mt-2 text-sm text-stone-600">Profil doğrulaması, değerlendirme/puan ve şikayet sistemiyle topluluğu güvende tutuyoruz. Şüpheli bir ilan görürsen "şikayet et" ile bize bildir.</p>
            <a href="{{ route('register') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-6 py-2.5 font-semibold text-white hover:bg-emerald-700">Ücretsiz Başla</a>
        </div>
    </div>
</x-layouts.app>
