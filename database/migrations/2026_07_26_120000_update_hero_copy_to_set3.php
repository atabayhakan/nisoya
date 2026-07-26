<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * Slogan Set 3 ("soru" kancası, 2026-07-26 sahibin seçimi): ana sayfa hero
 * metinlerini yeniler. Seeder eski varsayılanları DB'ye yazdığı için yalnız
 * config varsayılanını değiştirmek canlıda etkisiz kalırdı — kayıtlar burada
 * bilinçli olarak ÜZERİNE YAZILIR (metin kararı panelden her an değiştirilebilir).
 */
return new class extends Migration
{
    private const YENI_METINLER = [
        'home.hero_satir1' => 'Nakliyeci mi, hoca mı?',
        'home.hero_vurgu' => 'Hepsi burada, Türkçe.',
        'home.hero_satir2' => '',
        'home.hero_aciklama' => 'Taşınma, ders, tamir, ev yemeği, davetiye — yaşadığın şehirde Türkçe konuşan birini dakikalar içinde bul, direkt yaz.',
        'home.arama_placeholder' => "Kim lazım? (ör. Berlin'de nakliyeci)",
        'home.populer_metin' => 'Popüler: taşınma · matematik dersi · araba tamiri · davetiye · ikinci el',
        'home.cta_baslik' => 'İlk ilanın 3 dakikada yayında, kuruş ödemeden.',
        'home.cta_metin' => 'İlan ver, mesajlaş, anlaş — hepsi tamamen ücretsiz.',
        'home.cta_buton' => 'Ücretsiz ilan ver',
    ];

    public function up(): void
    {
        // Settings::setMany: upsert + doğru grup (config'ten) + cache temizliği.
        // Migration'da auth olmadığı için denetim izi sessizce atlanır.
        Settings::setMany(self::YENI_METINLER);
    }

    public function down(): void
    {
        // Eski metinlere dönüş panelden yapılır (İçerik → Anasayfa); veri
        // migration'ı geri alınmaz.
    }
};
