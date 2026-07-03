<?php

/*
|--------------------------------------------------------------------------
| Site içerik (metin) ayarları — tek kaynak
|--------------------------------------------------------------------------
| Buradaki her alan: hem seeder ile DB'ye yazılır, hem setting() için
| varsayılan değerdir, hem de admin "İçerik" formunu otomatik üretir.
| Yeni düzenlenebilir bir metin eklemek için tek satır eklemek yeter.
*/

return [

    'groups' => [
        'anasayfa' => 'Anasayfa',
        'header' => 'Üst Menü (Header)',
        'footer' => 'Alt Bilgi (Footer)',
        'genel' => 'Genel',
        'reklam' => 'Reklam & Analiz',
        'bagis' => 'Bağış',
    ],

    // key => [group, label, type (text|textarea), default]
    'fields' => [

        // --- Genel ---
        'genel.site_adi' => ['group' => 'genel', 'label' => 'Site adı', 'type' => 'text', 'default' => 'Nisoya'],
        'genel.slogan' => ['group' => 'genel', 'label' => 'Slogan', 'type' => 'text', 'default' => 'Ne İş Olursa Yaparım'],

        // --- Anasayfa: Hero ---
        'home.hero_badge' => ['group' => 'anasayfa', 'label' => 'Hero rozet metni', 'type' => 'text', 'default' => '🌍 Yurt dışındaki Türkler için'],
        'home.hero_satir1' => ['group' => 'anasayfa', 'label' => 'Başlık — 1. satır', 'type' => 'text', 'default' => 'Yeteneğini paraya dönüştür,'],
        'home.hero_vurgu' => ['group' => 'anasayfa', 'label' => 'Başlık — vurgulu kısım', 'type' => 'text', 'default' => 'kendi insanından'],
        'home.hero_satir2' => ['group' => 'anasayfa', 'label' => 'Başlık — vurgudan sonrası', 'type' => 'text', 'default' => 'hizmet al'],
        'home.hero_aciklama' => ['group' => 'anasayfa', 'label' => 'Hero açıklama', 'type' => 'textarea', 'default' => "İngilizce ders mi veriyorsun, taşınmada mı yardım ediyorsun, ev yemeği mi yapıyorsun? Nisoya'da yeteneğini ilan et; bulunduğun ülkedeki Türklerle güvenle buluş."],
        'home.arama_placeholder' => ['group' => 'anasayfa', 'label' => 'Arama kutusu metni', 'type' => 'text', 'default' => 'Ne arıyorsun? (ör. İngilizce öğretmeni)'],
        'home.populer_metin' => ['group' => 'anasayfa', 'label' => 'Popüler aramalar satırı', 'type' => 'text', 'default' => 'Popüler: dil dersi · taşınma · ev yemeği · web tasarım · tercüme'],

        // --- Anasayfa: Değer önerileri ---
        'home.deger1_baslik' => ['group' => 'anasayfa', 'label' => 'Değer 1 — başlık', 'type' => 'text', 'default' => 'Tamamen Türkçe'],
        'home.deger1_metin' => ['group' => 'anasayfa', 'label' => 'Değer 1 — metin', 'type' => 'text', 'default' => 'Kendi dilinde, kendi insanınla.'],
        'home.deger2_baslik' => ['group' => 'anasayfa', 'label' => 'Değer 2 — başlık', 'type' => 'text', 'default' => 'Güvenli topluluk'],
        'home.deger2_metin' => ['group' => 'anasayfa', 'label' => 'Değer 2 — metin', 'type' => 'text', 'default' => 'Değerlendirme ve doğrulanmış üyeler.'],
        'home.deger3_baslik' => ['group' => 'anasayfa', 'label' => 'Değer 3 — başlık', 'type' => 'text', 'default' => 'Ücretsiz ilan'],
        'home.deger3_metin' => ['group' => 'anasayfa', 'label' => 'Değer 3 — metin', 'type' => 'text', 'default' => 'İlan vermek tamamen ücretsiz.'],

        // --- Anasayfa: Nasıl çalışır ---
        'home.nasil_baslik' => ['group' => 'anasayfa', 'label' => 'Nasıl çalışır — başlık', 'type' => 'text', 'default' => 'Nasıl çalışır?'],
        'home.adim1_baslik' => ['group' => 'anasayfa', 'label' => 'Adım 1 — başlık', 'type' => 'text', 'default' => 'Ücretsiz kayıt ol'],
        'home.adim1_metin' => ['group' => 'anasayfa', 'label' => 'Adım 1 — metin', 'type' => 'textarea', 'default' => 'Birkaç dakikada hesabını oluştur, bulunduğun ülke ve şehri seç.'],
        'home.adim2_baslik' => ['group' => 'anasayfa', 'label' => 'Adım 2 — başlık', 'type' => 'text', 'default' => 'İlanını ver veya ara'],
        'home.adim2_metin' => ['group' => 'anasayfa', 'label' => 'Adım 2 — metin', 'type' => 'textarea', 'default' => 'Yeteneğini/hizmetini ilan et ya da ihtiyacın olan hizmeti ara.'],
        'home.adim3_baslik' => ['group' => 'anasayfa', 'label' => 'Adım 3 — başlık', 'type' => 'text', 'default' => 'Mesajlaş, anlaş'],
        'home.adim3_metin' => ['group' => 'anasayfa', 'label' => 'Adım 3 — metin', 'type' => 'textarea', 'default' => 'Karşı tarafla mesajlaş, güvenle anlaş. Ödeme aranızda.'],

        // --- Anasayfa: CTA ---
        'home.cta_baslik' => ['group' => 'anasayfa', 'label' => 'CTA — başlık', 'type' => 'text', 'default' => 'Bir yeteneğin mutlaka vardır.'],
        'home.cta_metin' => ['group' => 'anasayfa', 'label' => 'CTA — metin', 'type' => 'text', 'default' => 'Hadi onu paraya dönüştür. İlan vermek tamamen ücretsiz.'],
        'home.cta_buton' => ['group' => 'anasayfa', 'label' => 'CTA — buton', 'type' => 'text', 'default' => 'Hemen Başla'],

        // --- Üst Menü (Header) ---
        'header.ozel_kod' => ['group' => 'header', 'label' => 'Header özel kod (</head> öncesi)', 'type' => 'code', 'default' => ''],

        // --- Footer ---
        'footer.aciklama' => ['group' => 'footer', 'label' => 'Footer açıklama', 'type' => 'textarea', 'default' => 'Ne İş Olursa Yaparım. Yurt dışındaki Türklerin kendi aralarında yetenek ve hizmet pazaryeri.'],
        'footer.telif_metni' => ['group' => 'footer', 'label' => 'Telif hakkı metni (yıl otomatik eklenir)', 'type' => 'text', 'default' => 'Nisoya. Tüm hakları saklıdır.'],
        'footer.sosyal_instagram' => ['group' => 'footer', 'label' => 'Instagram bağlantısı', 'type' => 'text', 'default' => ''],
        'footer.sosyal_facebook' => ['group' => 'footer', 'label' => 'Facebook bağlantısı', 'type' => 'text', 'default' => ''],
        'footer.sosyal_whatsapp' => ['group' => 'footer', 'label' => 'WhatsApp bağlantısı (örn: wa.me/49...)', 'type' => 'text', 'default' => ''],
        'footer.ozel_kod' => ['group' => 'footer', 'label' => 'Footer özel kod (</body> öncesi)', 'type' => 'code', 'default' => ''],

        // --- Reklam & Analiz ---
        'reklam.adsense_publisher' => ['group' => 'reklam', 'label' => 'AdSense Yayıncı ID (ca-pub-...)', 'type' => 'text', 'default' => ''],
        'reklam.adsense_auto_ads_kod' => ['group' => 'reklam', 'label' => 'AdSense Auto Ads kodu (opsiyonel — AdSense hesabından kopyala)', 'type' => 'code', 'default' => ''],
        'reklam.analytics_measurement_id' => ['group' => 'reklam', 'label' => 'Google Analytics Ölçüm ID (G-XXXXXXXXXX)', 'type' => 'text', 'default' => ''],
        'reklam.analytics_ozel_kod' => ['group' => 'reklam', 'label' => 'Analytics/başka bir ölçüm aracı için özel kod (opsiyonel)', 'type' => 'code', 'default' => ''],
        'reklam.adsense_aciklama' => ['group' => 'reklam', 'label' => 'Reklam alanı bilgilendirme metni (opsiyonel)', 'type' => 'textarea', 'default' => 'Sitemiz gelirini Google AdSense reklamlarından elde eder. Reklamlar, ilan veren kişilerin masraflarını karşılamamıza ve hizmeti ücretsiz sunmamıza yardımcı olur.'],

        // --- Bağış ---
        'bagis.baslik' => ['group' => 'bagis', 'label' => 'Bağış başlığı', 'type' => 'text', 'default' => 'Nisoya ücretsiz kalacak'],
        'bagis.metin' => ['group' => 'bagis', 'label' => 'Bağış açıklaması', 'type' => 'textarea', 'default' => "Nisoya tamamen ücretsiz bir hizmettir. Sunucu, alan adı ve bakım giderlerini karşılamak için bağışlarınız bize güç verir. İstediğiniz yöntemi seçebilirsiniz:"],
        'bagis.paypal_me' => ['group' => 'bagis', 'label' => 'PayPal.me bağlantısı (örn: paypal.me/nisoya)', 'type' => 'text', 'default' => ''],
        'bagis.iban' => ['group' => 'bagis', 'label' => 'IBAN (TR ile başlayan)', 'type' => 'text', 'default' => ''],
        'bagis.iban_sahibi' => ['group' => 'bagis', 'label' => 'IBAN hesap sahibi', 'type' => 'text', 'default' => ''],
        'bagis.maliyet_baslik' => ['group' => 'bagis', 'label' => 'Maliyet şeffaflığı — başlık (boş bırakılırsa bölüm gösterilmez)', 'type' => 'text', 'default' => 'Bağışların nereye gittiği'],
        'bagis.maliyet1' => ['group' => 'bagis', 'label' => 'Maliyet kalemi 1 (örn: Sunucu barındırma — ayda ~15€)', 'type' => 'text', 'default' => ''],
        'bagis.maliyet2' => ['group' => 'bagis', 'label' => 'Maliyet kalemi 2 (örn: Alan adı — yılda ~15€)', 'type' => 'text', 'default' => ''],
        'bagis.maliyet3' => ['group' => 'bagis', 'label' => 'Maliyet kalemi 3 (örn: Bakım ve geliştirme — gönüllü)', 'type' => 'text', 'default' => ''],
    ],
];
