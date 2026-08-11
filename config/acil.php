<?php

/*
|--------------------------------------------------------------------------
| Acil yardım numaraları
|--------------------------------------------------------------------------
|
| BU DOSYA GÜVENLİK VERİSİDİR. Yanlış bir numara, gerçekten acil durumdaki
| birini yanlış yere yönlendirir. Bu yüzden:
|
|   · Numaralar TAHMİNLE yazılmaz; kaynak kontrol edilerek girilir.
|   · Her ülkede `dogrulandi` tarihi vardır; eskiyen kayıt gözden geçirilir.
|   · Panelden düzenlenebilir YAPILMADI (bilerek): bu veri kod incelemesinden
|     geçmeli, bir yönetim ekranından tek tıkla değiştirilebilir olmamalı.
|
| ARAYÜZ SÖZLEŞMESİ: hangi numara gösterilirse gösterilsin, kullanıcıya
| "emin değilsen 112'yi dene" güvenlik ağı ve resmî kaynağa gitme yolu
| gösterilir. Nisoya bir ilan platformudur, acil servis değildir.
|
| `genel`  : tek numara aranacaksa bu aranır (dispatch'e ulaşır)
| `polis`  : YALNIZ genel numaradan farklıysa yazılır (ör. Almanya 110)
| `not`    : kullanıcıya gösterilecek kısa açıklama
|
*/

return [

    /*
     * T.C. Dışişleri Bakanlığı Konsolosluk Çağrı Merkezi — 7/24, dünyanın
     * her yerinden aranabilir, 6 dilde hizmet veriyor. Yurt dışındaki bir
     * Türk için bu, acil durumda en değerli tek numaradır: pasaport kaybı,
     * gözaltı, hastane, vefat gibi konsolosluk gerektiren hâllerde.
     * Kaynak: mfa.gov.tr — Konsolosluk Çağrı Merkezi sayfası.
     */
    'konsolosluk_cagri_merkezi' => [
        'numara' => '+903122922929',
        'gosterim' => '+90 312 292 29 29',
        'aciklama' => 'T.C. Dışişleri Konsolosluk Çağrı Merkezi — 7/24, dünyanın her yerinden',
    ],

    /*
     * Doğrulama turu: 2026-08-11.
     * AB/EEA: 112 tüm üye ülkelerde çalışır (1991'den beri tek acil numara).
     * Sovyet mirası numaralandırma (AZ/KZ/KG/UZ/TM/RU): 112 genel, 101 itfaiye,
     * 102 polis, 103 ambulans.
     */
    'ulkeler' => [
        // --- AB / EEA — 112 her yerde geçerli ---
        'DE' => ['genel' => '112', 'polis' => '110', 'not' => 'Ambulans ve itfaiye 112, polis 110.', 'dogrulandi' => '2026-08-11'],
        'NL' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'FR' => ['genel' => '112', 'not' => '112 tüm servisler. Ayrıca ambulans 15, polis 17, itfaiye 18.', 'dogrulandi' => '2026-08-11'],
        'AT' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'BE' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'CH' => ['genel' => '112', 'not' => '112 çalışır. Ayrıca polis 117, itfaiye 118, ambulans 144.', 'dogrulandi' => '2026-08-11'],
        'SE' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'NO' => ['genel' => '112', 'not' => '112 polis. Ayrıca itfaiye 110, ambulans 113.', 'dogrulandi' => '2026-08-11'],
        'DK' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'IT' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'ES' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],
        'PL' => ['genel' => '112', 'not' => 'Tüm acil servisler 112.', 'dogrulandi' => '2026-08-11'],

        // --- Diğer Avrupa ---
        'GB' => ['genel' => '999', 'not' => '999 ana numara; 112 de çalışır.', 'dogrulandi' => '2026-08-11'],

        // --- Kuzey Amerika / Okyanusya ---
        'US' => ['genel' => '911', 'not' => 'Tüm acil servisler 911.', 'dogrulandi' => '2026-08-11'],
        'CA' => ['genel' => '911', 'not' => 'Tüm acil servisler 911.', 'dogrulandi' => '2026-08-11'],
        'AU' => ['genel' => '000', 'not' => '000 ana numara; cep telefonundan 112 de çalışır.', 'dogrulandi' => '2026-08-11'],

        // --- Türk dünyası (Sovyet mirası numaralandırma) ---
        'AZ' => ['genel' => '112', 'not' => '112 genel. Ayrıca itfaiye 101, polis 102, ambulans 103.', 'dogrulandi' => '2026-08-11'],
        'KZ' => ['genel' => '112', 'not' => '112 genel. Ayrıca itfaiye 101, polis 102, ambulans 103.', 'dogrulandi' => '2026-08-11'],
        'KG' => ['genel' => '112', 'not' => '112 genel. Ayrıca itfaiye 101, polis 102, ambulans 103.', 'dogrulandi' => '2026-08-11'],
        'UZ' => ['genel' => '112', 'not' => '112 genel. Ayrıca itfaiye 101, polis 102, ambulans 103.', 'dogrulandi' => '2026-08-11'],
        'TM' => ['genel' => '112', 'not' => '112 genel. Ayrıca itfaiye 101, polis 102, ambulans 103.', 'dogrulandi' => '2026-08-11'],
        'RU' => ['genel' => '112', 'not' => '112 genel. Ayrıca itfaiye 101, polis 102, ambulans 103.', 'dogrulandi' => '2026-08-11'],

        // --- Körfez ---
        'AE' => ['genel' => '999', 'not' => '999 polis. Ayrıca ambulans 998, itfaiye 997.', 'dogrulandi' => '2026-08-11'],
        'QA' => ['genel' => '999', 'not' => 'Tüm acil servisler 999.', 'dogrulandi' => '2026-08-11'],
        'SA' => ['genel' => '911', 'not' => '911 birleşik acil numara. Ayrıca polis 999, ambulans 997, itfaiye 998.', 'dogrulandi' => '2026-08-11'],
    ],
];
