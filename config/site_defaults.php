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
        'gorunum' => 'Görünüm (Marka)',
        'header' => 'Üst Menü (Header)',
        'footer' => 'Alt Bilgi (Footer)',
        'genel' => 'Genel',
        'iletisim' => 'İletişim Sayfası',
        'reklam' => 'Reklam & Analiz',
        'bagis' => 'Bağış',
        'nabiz' => 'Nisoya Nabzı',
        'giris' => 'Giriş Yöntemleri',
    ],

    // key => [group, label, type (text|textarea), default]
    'fields' => [

        // --- Genel ---
        'genel.site_adi' => ['group' => 'genel', 'label' => 'Site adı', 'type' => 'text', 'default' => 'Nisoya'],
        'genel.slogan' => ['group' => 'genel', 'label' => 'Slogan', 'type' => 'text', 'default' => 'Ne İş Olursa Yaparım'],

        // --- İletişim sayfası ---
        // 'iletisim.eposta' aynı zamanda yeni mesaj bildirimlerinin gönderileceği
        // adres olarak da kullanılır (bkz. ContactMessageController).
        'iletisim.eposta' => ['group' => 'iletisim', 'label' => 'İletişim e-postası', 'type' => 'text', 'default' => 'destek@nisoya.com'],
        'iletisim.telefon' => ['group' => 'iletisim', 'label' => 'Telefon (ops. — boşsa gösterilmez)', 'type' => 'text', 'default' => ''],
        'iletisim.adres' => ['group' => 'iletisim', 'label' => 'Adres (ops. — boşsa gösterilmez)', 'type' => 'textarea', 'default' => ''],
        'iletisim.calisma_saatleri' => ['group' => 'iletisim', 'label' => 'Yanıt süresi / çalışma saatleri notu (ops.)', 'type' => 'text', 'default' => 'Genelde 24 saat içinde dönüş yaparız.'],

        // --- Anasayfa: Hero ---
        'home.hero_badge' => ['group' => 'anasayfa', 'label' => 'Hero rozet metni', 'type' => 'text', 'default' => '🌍 Yurt dışındaki Türkler için'],
        // Slogan Set 3 ("soru" kancası, 2026-07-26): tek nefeste hem satıcıya
        // hem alıcıya seslenen eski başlık yerine, 3 saniyede "ne işe yarar"ı
        // kapatan soru formatı. Vurgu satırı birincil renkte render edilir;
        // hero_satir2 bilinçli boş (başlık 2 satır). Canlı DB kayıtları
        // UpdateHeroCopyToSet3 migration'ıyla güncellenir.
        'home.hero_satir1' => ['group' => 'anasayfa', 'label' => 'Başlık — 1. satır', 'type' => 'text', 'default' => 'Tarif etmeye çalışma.'],
        'home.hero_vurgu' => ['group' => 'anasayfa', 'label' => 'Başlık — vurgulu kısım', 'type' => 'text', 'default' => 'Türkçe anlat, iş bitsin.'],
        'home.hero_satir2' => ['group' => 'anasayfa', 'label' => 'Başlık — vurgudan sonrası (ops.)', 'type' => 'text', 'default' => ''],
        'home.hero_aciklama' => ['group' => 'anasayfa', 'label' => 'Hero açıklama', 'type' => 'textarea', 'default' => 'Nakliyeci, hoca, tamirci, kuaför, tercüman — şehrindeki Türkçe konuşan kişiyi bul, aracısız yaz, işini gör.'],
        // Kısa tutuldu: dokunmatikte alanlar 16px'e çıktığı için uzun
        // placeholder kırpılıyordu; örnek zaten çip satırında duruyor.
        'home.arama_placeholder' => ['group' => 'anasayfa', 'label' => 'Arama kutusu metni', 'type' => 'text', 'default' => 'Kim lazım?'],
        'home.populer_metin' => ['group' => 'anasayfa', 'label' => 'Popüler aramalar satırı', 'type' => 'text', 'default' => 'Popüler: taşınma · matematik dersi · araba tamiri · davetiye · ikinci el'],

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
        // Set 3'ün "satıcı bandı" karşılığı — ölçülebilir ve tutulabilir söz
        // ("3 dakika + sıfır ücret"), doğrulanamayan likidite iddiası yok.
        'home.cta_baslik' => ['group' => 'anasayfa', 'label' => 'CTA — başlık', 'type' => 'text', 'default' => 'İlk ilanın 3 dakikada yayında, kuruş ödemeden.'],
        'home.cta_metin' => ['group' => 'anasayfa', 'label' => 'CTA — metin', 'type' => 'text', 'default' => 'İlan ver, mesajlaş, anlaş — hepsi tamamen ücretsiz.'],
        'home.cta_buton' => ['group' => 'anasayfa', 'label' => 'CTA — buton', 'type' => 'text', 'default' => 'Ücretsiz ilan ver'],

        // --- Görünüm (Marka) ---
        'gorunum.logo_path' => ['group' => 'gorunum', 'label' => 'Logo (opsiyonel — yüklenmezse varsayılan "N" işareti kullanılır)', 'type' => 'image', 'default' => ''],
        'gorunum.favicon_path' => ['group' => 'gorunum', 'label' => 'Favicon (opsiyonel, kare görsel önerilir)', 'type' => 'image', 'default' => ''],
        'gorunum.marka_rengi' => [
            'group' => 'gorunum',
            'label' => 'Marka rengi',
            'type' => 'select',
            'options' => array_map(fn (array $c) => $c['label'], config('brand_colors', [])),
            'default' => 'emerald',
        ],

        // --- Hero Yöneticisi (Vitrin — Faz P3) ---
        // BİLİNÇLİ olarak 'groups' listesinde YOK: genel İçerik formunda
        // render edilmez, kendi sayfası var (HeroYoneticisi). Boş bırakılan
        // metin alanları klasik `home.hero_*` değerlerine düşer (App\Support\Hero),
        // böylece panel hiç açılmadan da vitrin hero'su doğru çalışır.
        // Kampanya/A-B alanları BUGÜN EKLENMEZ (ölü alan yasağı — 2026-07-22 denetimi).
        'hero.duzen' => ['group' => 'hero', 'label' => 'Hero düzeni', 'type' => 'select', 'options' => ['bento' => 'Bento Vitrin', 'sahne' => 'Sahne'], 'default' => 'bento'],
        'hero.rozet' => ['group' => 'hero', 'label' => 'Rozet metni', 'type' => 'text', 'default' => ''],
        'hero.baslik' => ['group' => 'hero', 'label' => 'Başlık — 1. satır', 'type' => 'text', 'default' => ''],
        'hero.vurgu' => ['group' => 'hero', 'label' => 'Başlık — vurgulu satır', 'type' => 'text', 'default' => ''],
        'hero.alt_baslik' => ['group' => 'hero', 'label' => 'Alt başlık', 'type' => 'textarea', 'default' => ''],
        'hero.cta1_etiket' => ['group' => 'hero', 'label' => 'Birincil buton — etiket', 'type' => 'text', 'default' => ''],
        'hero.cta1_url' => ['group' => 'hero', 'label' => 'Birincil buton — URL', 'type' => 'text', 'default' => ''],
        'hero.cta2_aktif' => ['group' => 'hero', 'label' => 'İkincil buton açık', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '0'],
        'hero.cta2_etiket' => ['group' => 'hero', 'label' => 'İkincil buton — etiket', 'type' => 'text', 'default' => ''],
        'hero.cta2_url' => ['group' => 'hero', 'label' => 'İkincil buton — URL', 'type' => 'text', 'default' => ''],
        'hero.arkaplan_tipi' => ['group' => 'hero', 'label' => 'Arka plan tipi', 'type' => 'select', 'options' => ['yok' => 'Yok (degrade)', 'gorsel' => 'Görsel', 'video' => 'Video'], 'default' => 'yok'],
        'hero.gorsel_masaustu' => ['group' => 'hero', 'label' => 'Arka plan görseli (masaüstü)', 'type' => 'image', 'default' => ''],
        'hero.gorsel_mobil' => ['group' => 'hero', 'label' => 'Arka plan görseli (mobil)', 'type' => 'image', 'default' => ''],
        'hero.video_url' => ['group' => 'hero', 'label' => 'Arka plan video URL (mp4/webm)', 'type' => 'text', 'default' => ''],
        'hero.overlay' => ['group' => 'hero', 'label' => 'Karartma yüzdesi', 'type' => 'text', 'default' => '58'],
        'hero.odak' => ['group' => 'hero', 'label' => 'Görsel odak noktası', 'type' => 'text', 'default' => 'merkez'],
        'hero.bloklar' => ['group' => 'hero', 'label' => 'Blok sırası ve açık/kapalı durumu (JSON)', 'type' => 'text', 'default' => ''],

        // Zamanlanmış kampanya (Faz P4c). "Şu an aktif mi" kıyaslaması HER
        // RENDER'DA yapılır — cache'e yalnız ham tarihler girer, o yüzden
        // başlangıç/bitiş anında cache temizlemeye veya zamanlanmış işe
        // GEREK YOKTUR (bkz. App\Support\Hero::kampanyaAktifMi).
        'hero.kampanya_aktif' => ['group' => 'hero', 'label' => 'Kampanya açık', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '0'],
        'hero.kampanya_ad' => ['group' => 'hero', 'label' => 'Kampanya adı (yalnız panelde görünür)', 'type' => 'text', 'default' => ''],
        'hero.kampanya_baslangic' => ['group' => 'hero', 'label' => 'Kampanya başlangıcı', 'type' => 'text', 'default' => ''],
        'hero.kampanya_bitis' => ['group' => 'hero', 'label' => 'Kampanya bitişi', 'type' => 'text', 'default' => ''],
        'hero.kampanya_rozet' => ['group' => 'hero', 'label' => 'Kampanya — rozet metni', 'type' => 'text', 'default' => ''],
        'hero.kampanya_baslik' => ['group' => 'hero', 'label' => 'Kampanya — başlık 1. satır', 'type' => 'text', 'default' => ''],
        'hero.kampanya_vurgu' => ['group' => 'hero', 'label' => 'Kampanya — vurgulu satır', 'type' => 'text', 'default' => ''],
        'hero.kampanya_alt_baslik' => ['group' => 'hero', 'label' => 'Kampanya — alt başlık', 'type' => 'textarea', 'default' => ''],

        // --- Tema motoru (Vitrin) ---
        // BİLİNÇLİ olarak grup anahtarı 'groups' listesinde YOK — genel
        // İçerik formunda render edilmez (AI alanlarıyla aynı desen). Tek
        // yönetim yeri: Tasarım Modu sayfasındaki Klasik/Vitrin segmenti
        // (TasarimAyarlari::secTema). Burada tanımlı olması setMany'nin
        // doğru grup yazması ve Settings::get varsayılanı içindir.
        'gorunum.tema' => ['group' => 'tema_motoru', 'label' => 'Site teması', 'type' => 'select', 'options' => ['klasik' => 'Klasik', 'vitrin' => 'Vitrin'], 'default' => 'klasik'],

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
        'bagis.aktif' => ['group' => 'bagis', 'label' => 'Bağış bölümü (💛 Destek Ol butonu + modal)', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],
        'bagis.baslik' => ['group' => 'bagis', 'label' => 'Bağış başlığı', 'type' => 'text', 'default' => 'Nisoya ücretsiz kalacak'],
        'bagis.metin' => ['group' => 'bagis', 'label' => 'Bağış açıklaması', 'type' => 'textarea', 'default' => 'Nisoya tamamen ücretsiz bir hizmettir. Sunucu, alan adı ve bakım giderlerini karşılamak için bağışlarınız bize güç verir. İstediğiniz yöntemi seçebilirsiniz:'],
        'bagis.paypal_me' => ['group' => 'bagis', 'label' => 'PayPal.me bağlantısı (örn: paypal.me/nisoya)', 'type' => 'text', 'default' => ''],
        'bagis.iban' => ['group' => 'bagis', 'label' => 'IBAN (TR ile başlayan)', 'type' => 'text', 'default' => ''],
        'bagis.iban_sahibi' => ['group' => 'bagis', 'label' => 'IBAN hesap sahibi', 'type' => 'text', 'default' => ''],
        'bagis.maliyet_baslik' => ['group' => 'bagis', 'label' => 'Maliyet şeffaflığı — başlık (boş bırakılırsa bölüm gösterilmez)', 'type' => 'text', 'default' => 'Bağışların nereye gittiği'],
        'bagis.maliyet1' => ['group' => 'bagis', 'label' => 'Maliyet kalemi 1 (örn: Sunucu barındırma — ayda ~15€)', 'type' => 'text', 'default' => ''],
        'bagis.maliyet2' => ['group' => 'bagis', 'label' => 'Maliyet kalemi 2 (örn: Alan adı — yılda ~15€)', 'type' => 'text', 'default' => ''],
        'bagis.maliyet3' => ['group' => 'bagis', 'label' => 'Maliyet kalemi 3 (örn: Bakım ve geliştirme — gönüllü)', 'type' => 'text', 'default' => ''],

        // --- Yapay Zeka (kamera-önce ilan görüntü analizi) ---
        // NOT: bu alanlar bilinçli olarak 'groups' listesine EKLENMEDİ — genel
        // İçerik formunda render edilmezler; kendi sayfaları var (YapayZekaAyarlari).
        // Varsayılanlar boş: DB boşsa Settings::get boş döner → mergeRuntimeConfig
        // env/config varsayılanına düşer (DB > env > kod). API anahtarı hassas
        // olduğundan varsayılanı boş.
        // Ana anahtar: '0' ise sağlayıcı/anahtar girili ve alt özellikler açık
        // olsa bile TÜM AI kapanır (bkz. mergeAiConfig — sağlayıcı çökerse tek düğme).
        'ai.aktif' => ['group' => 'yapay_zeka', 'label' => 'Yapay zeka açık', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],
        'ai.saglayici' => ['group' => 'yapay_zeka', 'label' => 'Sağlayıcı', 'type' => 'select', 'options' => [
            'openrouter' => 'OpenRouter',
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
        ], 'default' => ''],
        'ai.api_anahtari' => ['group' => 'yapay_zeka', 'label' => 'API anahtarı', 'type' => 'password', 'default' => ''],

        // --- Giriş yöntemleri (Google ile giriş) ---
        // `giris.google_client_secret` Settings::SIRLI_ANAHTARLAR listesinde:
        // veritabanında şifreli durur (bkz. Settings::sirliMi).
        'giris.google_aktif' => ['group' => 'giris', 'label' => 'Google ile giriş açık', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '0'],
        'giris.google_client_id' => ['group' => 'giris', 'label' => 'Google Client ID', 'type' => 'text', 'default' => ''],
        'giris.google_client_secret' => ['group' => 'giris', 'label' => 'Google Client Secret', 'type' => 'password', 'default' => ''],
        'ai.model' => ['group' => 'yapay_zeka', 'label' => 'Model', 'type' => 'text', 'default' => ''],
        'ai.hizli_ilan_aktif' => ['group' => 'yapay_zeka', 'label' => 'Fotoğrafla hızlı ilan', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],
        'ai.moderasyon_aktif' => ['group' => 'yapay_zeka', 'label' => 'Görsel moderasyonu (uygunsuz içerik ön-elemesi)', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],

        // --- E-posta (SMTP) ---
        // NOT: 'yapay_zeka' gibi bu grup da bilinçli olarak 'groups' listesine
        // EKLENMEDİ — genel İçerik formunda render edilmez; kendi sayfası var
        // (MailAyarlari). Parola hassastır. Varsayılanlar boş: DB boşsa
        // AppServiceProvider::mergeMailConfig() env/config'e düşer (DB > env > kod).
        'mail.host' => ['group' => 'mail', 'label' => 'SMTP sunucusu', 'type' => 'text', 'default' => ''],
        'mail.port' => ['group' => 'mail', 'label' => 'Port', 'type' => 'text', 'default' => ''],
        'mail.username' => ['group' => 'mail', 'label' => 'Kullanıcı adı', 'type' => 'text', 'default' => ''],
        'mail.password' => ['group' => 'mail', 'label' => 'Parola', 'type' => 'password', 'default' => ''],
        'mail.encryption' => ['group' => 'mail', 'label' => 'Şifreleme', 'type' => 'select', 'options' => ['ssl' => 'SSL (port 465)', 'tls' => 'TLS/STARTTLS (port 587)'], 'default' => ''],
        'mail.from_address' => ['group' => 'mail', 'label' => 'Gönderen e-posta', 'type' => 'text', 'default' => ''],
        'mail.from_name' => ['group' => 'mail', 'label' => 'Gönderen adı', 'type' => 'text', 'default' => ''],

        // --- Dikey modüller (aç/kapa) — kendi sayfası var (Moduller); groups'a
        // eklenmedi. Kapalıysa public rota 404, yeni içerik yok (bkz. App\Support\Modules).
        'modul.emlak' => ['group' => 'modul', 'label' => 'Emlak', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],
        'modul.vasita' => ['group' => 'modul', 'label' => 'Vasıta', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],
        'modul.davetiye' => ['group' => 'modul', 'label' => 'Davetiye', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],
        'modul.is_ilanlari' => ['group' => 'modul', 'label' => 'İş İlanları', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '1'],

        // --- Duyuru bandı (site üstü tek satır şerit) — kendi sayfası var
        // (DuyuruBandi); groups'a eklenmedi. Kapalı/boşsa hiç render edilmez.
        'duyuru.aktif' => ['group' => 'duyuru', 'label' => 'Duyuru bandı', 'type' => 'select', 'options' => ['1' => 'Açık', '0' => 'Kapalı'], 'default' => '0'],
        'duyuru.metin' => ['group' => 'duyuru', 'label' => 'Duyuru metni', 'type' => 'textarea', 'default' => ''],
        'duyuru.link' => ['group' => 'duyuru', 'label' => 'Bağlantı (opsiyonel)', 'type' => 'text', 'default' => ''],
        'duyuru.link_metni' => ['group' => 'duyuru', 'label' => 'Bağlantı metni (opsiyonel)', 'type' => 'text', 'default' => ''],
        'duyuru.renk' => ['group' => 'duyuru', 'label' => 'Renk', 'type' => 'select', 'options' => ['marka' => 'Marka (yeşil)', 'uyari' => 'Uyarı (amber)', 'onemli' => 'Önemli (kırmızı)'], 'default' => 'marka'],
        'duyuru.kapatilabilir' => ['group' => 'duyuru', 'label' => 'Ziyaretçi kapatabilsin', 'type' => 'select', 'options' => ['1' => 'Evet', '0' => 'Hayır'], 'default' => '1'],

        // --- SEO (arama motoru + paylaşım) — kendi sayfası var (SeoAyarlari).
        'seo.default_title' => ['group' => 'seo', 'label' => 'Varsayılan başlık', 'type' => 'text', 'default' => 'Nisoya — Ne İş Olursa Yaparım'],
        'seo.default_description' => ['group' => 'seo', 'label' => 'Varsayılan açıklama', 'type' => 'textarea', 'default' => 'Yurt dışındaki Türklerin yetenek, hizmet ve ev ürünleri pazaryeri. Kendi insanından güvenle hizmet al, yeteneğini paraya dönüştür.'],
        'seo.og_image' => ['group' => 'seo', 'label' => 'Paylaşım görseli (OG — 1200×630 önerilir)', 'type' => 'image', 'default' => ''],
        'seo.robots_index' => ['group' => 'seo', 'label' => 'Arama motorlarında görünür', 'type' => 'select', 'options' => ['1' => 'Görünür', '0' => 'Gizli (noindex)'], 'default' => '1'],

        // --- Nisoya Nabzı: topluluk hedefi + şehir elçileri ---
        'nabiz.hedef_sayi' => ['group' => 'nabiz', 'label' => 'Hedef sayı (0 = özelliği tamamen gizle)', 'type' => 'text', 'default' => '0'],
        'nabiz.hedef_metrik' => ['group' => 'nabiz', 'label' => 'Hedef neyi sayar?', 'type' => 'select', 'options' => ['yeni_uye' => 'Bu ay yeni üye', 'yeni_ilan' => 'Bu ay yeni ilan'], 'default' => 'yeni_uye'],
        'nabiz.hedef_baslik' => ['group' => 'nabiz', 'label' => 'Hedef başlığı', 'type' => 'text', 'default' => 'Bu ay hedefimiz'],
        'nabiz.odul_mesaji' => ['group' => 'nabiz', 'label' => 'Ödül/motivasyon mesajı (ops.)', 'type' => 'text', 'default' => ''],
        'nabiz.hikaye_duvari_aktif' => ['group' => 'nabiz', 'label' => 'Gurbet Günlüğü (anonim hikaye duvarı)', 'type' => 'select', 'options' => ['0' => 'Kapalı', '1' => 'Açık'], 'default' => '0'],
    ],
];
