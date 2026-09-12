<div class="space-y-4 text-xs">
    {{-- Üst Özet Kartı --}}
    <div class="p-4 rounded-xl border border-primary-200 bg-primary-50/50 dark:border-primary-900/50 dark:bg-primary-950/20">
        <div class="flex items-center gap-2 mb-1.5">
            <x-filament::icon icon="heroicon-m-sparkles" class="h-4 w-4 text-primary-700 dark:text-primary-400" />
            <span class="font-bold text-sm text-primary-950 dark:text-primary-200">
                Eylül 2026 Büyüme ve Tersine Katılım Stratejisi
            </span>
        </div>
        <p class="text-gray-800 dark:text-gray-200 leading-relaxed">
            Platform verileri analiz edildi: Toplam <strong>{{ number_format($metrikler['toplam_kesif']) }}</strong> aday keşfedildi, <strong>{{ number_format($metrikler['turk_isletmeler']) }}</strong> (%{{ $metrikler['turk_orani'] }}) işletme Türk olarak doğrulandı. Mevcut vitrin sahiplenme dönüşüm oranı <strong>%{{ $metrikler['donusum_orani'] }}</strong> seviyesindedir.
        </p>
    </div>

    {{-- Stratejik Tavsiyeler --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="p-3 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 space-y-2">
            <div class="font-semibold text-emerald-800 dark:text-emerald-400 flex items-center gap-1.5">
                <x-filament::icon icon="heroicon-m-chart-bar" class="h-4 w-4 text-emerald-700 dark:text-emerald-400" />
                <span>Yüksek Verimli Hedef Şehirler</span>
            </div>
            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                <li class="flex items-center gap-1.5">
                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">1.</span>
                    <span><strong>Almanya:</strong> Berlin, Köln, Frankfurt, Duisburg (%85+ isabet)</span>
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">2.</span>
                    <span><strong>Avusturya:</strong> Viyana (Wien) Favoriten & Ottakring</span>
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="text-emerald-700 dark:text-emerald-400 font-bold">3.</span>
                    <span><strong>İsviçre:</strong> Basel & Zürih Türk esnaf kümelenmeleri</span>
                </li>
            </ul>
        </div>

        <div class="p-3 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 space-y-2">
            <div class="font-semibold text-purple-800 dark:text-purple-400 flex items-center gap-1.5">
                <x-filament::icon icon="heroicon-m-bolt" class="h-4 w-4 text-purple-700 dark:text-purple-400" />
                <span>En Hızlı Dönüşen Meslek Dalları</span>
            </div>
            <ul class="space-y-1 text-gray-700 dark:text-gray-300">
                <li class="flex items-center gap-1.5">
                    <span class="text-purple-700 dark:text-purple-400 font-bold">•</span>
                    <span><strong>Lokanta & Kebap & Baklava:</strong> WhatsApp açılma oranı %98</span>
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="text-purple-700 dark:text-purple-400 font-bold">•</span>
                    <span><strong>Kuaför & Berber:</strong> Mobil randevu ilgisi yüksek</span>
                </li>
                <li class="flex items-center gap-1.5">
                    <span class="text-purple-700 dark:text-purple-400 font-bold">•</span>
                    <span><strong>Oto Tamir & Lastik:</strong> Türkçe hizmet arayan gurbetçiler için kritik</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- Aksiyon Planı --}}
    <div class="p-3 rounded-lg border border-gray-200 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-900/40 space-y-1.5">
        <div class="font-semibold text-gray-950 dark:text-white flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-m-check-badge" class="h-4 w-4 text-primary-700 dark:text-primary-400" />
            <span>Tersine Katılım (Reverse Onboarding) İpuçları</span>
        </div>
        <p class="text-gray-700 dark:text-gray-300">
            1. "Otomatik Vitrin Hazırla" seçeneğini açık tutarak keşfedilen Türk esnafları için hazır sayfa oluşturun.<br>
            2. Keşif Havuzu'nda bekleyen <strong>364 adayı</strong> "AI Kültürel Analiz" ile topluca inceleyip onaylayın.<br>
            3. WhatsApp üzerinden iletilen 15 saniyelik sahiplenme linklerinin doğrudan esnaf cep telefonuna gitmesini sağlayın.
        </p>
    </div>
</div>
