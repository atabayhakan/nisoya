<x-layouts.app title="Gizlilik Politikası — Nisoya">
    <div class="mx-auto max-w-3xl px-4 py-12">
        <h1 class="text-3xl font-bold text-stone-900">Gizlilik Politikası</h1>
        <p class="mt-2 text-sm text-stone-400">Son güncelleme: {{ date('d.m.Y') }} · Taslak metin (GDPR uyumu için hukuki inceleme önerilir).</p>

        <div class="prose prose-stone mt-6 max-w-none text-stone-700">
            <h2>1. Topladığımız veriler</h2>
            <p>Hesap bilgileri (ad, e-posta, ülke/şehir), ilan içerikleri, mesajlar ve teknik kayıtlar (oturum, IP) işlenir.</p>

            <h2>2. Kullanım amacı</h2>
            <p>Verileri hizmeti sunmak, güvenliği sağlamak, kullanıcıları buluşturmak ve platformu geliştirmek için kullanırız. Verilerini satmayız.</p>

            <h2>3. Çerezler</h2>
            <p>Oturum ve tercih çerezleri kullanılır. Tarayıcı ayarlarından çerezleri yönetebilirsin.</p>

            <h2>4. Haklarınız (GDPR)</h2>
            <p>Avrupa Birliği'nde yaşıyorsan; verilerine erişme, düzeltme, silme ve taşıma haklarına sahipsin. Talepler için <a href="{{ route('pages.contact') }}">bize ulaşabilirsin</a>.</p>

            <h2>5. Veri saklama</h2>
            <p>Veriler, hesabın aktif olduğu sürece ve yasal yükümlülükler gerektirdiği ölçüde saklanır. Hesabını silebilirsin.</p>
        </div>
    </div>
</x-layouts.app>
