<?php

/*
|--------------------------------------------------------------------------
| Kâhya — yönetim ajanı
|--------------------------------------------------------------------------
| Buradaki değerler VARSAYILANDIR. Panelden girilen ayarlar (site_settings
| tablosundaki `kahya.*` anahtarları) bunların üzerine biner.
|
| DİKKAT — env() YALNIZ BU DOSYADA: config dosyalarının dışında env()
| çağırmak üretimde sessizce null döner, çünkü deploy `config:cache`
| çalıştırır ve önbelleklenmiş config yüklendiğinde .env okunmaz.
| Bu ders bu depoda bir kez pahalıya patladı (bkz. EnvKullanimiTest).
*/

return [

    /*
    | Ajanın görünen adı — sohbet başlığı, karşılama kartı ve kendi
    | tanıtımında kullanılır. Panelden değiştirilebilir (Kâhya Ayarları).
    */
    'isim' => env('KAHYA_ISIM', 'Kâhya'),

    /*
    | Günlük raporun gönderileceği saat (sunucu saati, UTC).
    |
    | 03:30 medya temizliği ve 04:00 yedekten SONRA olmalı — rapor "son yedek"
    | durumunu doğru göstersin diye.
    */
    'rapor_saati' => env('KAHYA_RAPOR_SAATI', '07:30'),

    /*
    | Medya doğrulamada taranacak en fazla kayıt sayısı.
    |
    | Tam tarama büyük kütüphanede yavaşlar ve rapor her gün koşar. En yeni
    | kayıtlar önceliklidir: yeni yüklenen bir görselin kırık olması, iki
    | yıllık bir kaydınkinden daha acildir.
    */
    'medya_tarama_limiti' => env('KAHYA_MEDYA_LIMIT', 500),

    /*
    | Log özetinin kapsadığı saat penceresi.
    */
    'log_penceresi_saat' => env('KAHYA_LOG_PENCERESI', 24),

    /*
    | Kâhya Telegram — grup içi otomatik cevaplama (F6, 2026-09-11).
    | Tüm değerler panelden (Kâhya Telegram) değiştirilebilir; buradakiler
    | yalnız ilk kurulum varsayılanı. bot_token/webhook_sirri gerçek sır
    | olduğu için Settings::SIRLI_ANAHTARLAR'da şifreli tutulur.
    */
    'telegram' => [
        'bot_token' => env('KAHYA_TELEGRAM_TOKEN'),
        'webhook_sirri' => env('KAHYA_TELEGRAM_WEBHOOK_SIRRI'),
        'ulke_kodu' => env('KAHYA_TELEGRAM_ULKE', 'RU'),
        'aylik_mesaj_limiti' => env('KAHYA_TELEGRAM_LIMIT', 600),
    ],

];
