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
| `genel`        : tek numara aranacaksa bu aranır
| `genel_etiket` : genel numaranın NEYE ulaştığı ("Tüm acil servisler",
|                  "Ambulans ve itfaiye", "Polis"). ZORUNLU — gerekçe aşağıda.
| `polis`        : YALNIZ genel numaradan farklıysa yazılır (ör. Almanya 110)
| `ambulans`     : YALNIZ genel numaradan farklıysa yazılır (ör. İsviçre 144)
| `itfaiye`      : YALNIZ genel numaradan farklıysa yazılır (ör. Norveç 110)
| `not`          : düğmelerin ANLATAMADIĞI artık bilgi. Düğmede yazan bir
|                  numarayı burada tekrar yazma.
|
| ---------------------------------------------------------------------------
| 2026-08-12 — ÜÇ DEĞİŞİKLİK VE BİR DERS
|
| 1. DÜĞMELEŞTİRME. `polis`/`ambulans`/`itfaiye` alanları dolduruldu. Bu
|    numaralar daha önce yalnız `not` metninin İÇİNDE yazılıydı; acil durumda
|    numarayı okuyup elle tuşlamak saniye kaybettiriyordu. Artık tek dokunuş.
|
| 2. `genel_etiket` EKLENDİ. Almanya'da panelde görünen tek adlandırılmış hat
|    "Polis 110" idi; "Ambulans" kelimesi ekranda HİÇ geçmiyordu. Panik hâlindeki
|    insan kelime tarar, rakam taramaz — aradığı kelimeyi bulamayınca ya donar ya
|    yanlış hattı arar. Genel numara artık ne olduğunu söylüyor.
|
| 3. DERS — BÖLGESEL ŞABLON DOĞRULAMA DEĞİLDİR. Aşağıdaki "Sovyet mirası
|    numaralandırma" notu altı ülkeye (AZ/KZ/KG/UZ/TM/RU) TEK KALIPTAN
|    dolduruldu ve hepsine aynı `dogrulandi` tarihi yazıldı. Bağımsız denetim
|    (2026-08-12) beşinde doğru, TÜRKMENİSTAN'DA DÖRDÜNÜN DE YANLIŞ olduğunu
|    buldu: TM üç haneli sisteme hiç geçmemiş ve 112'yi hiç almamış. Şablon,
|    yanlış olduğu tek yerde de sessizce "doğrulanmış" göründü.
|
|    Buradan çıkan kural: `dogrulandi` tarihi ÜLKE BAZINDA kontrolü gösterir.
|    Bir grubu tek hamlede doldurup hepsine aynı tarihi yazma.
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
        'DE' => ['genel' => '112', 'genel_etiket' => 'Ambulans ve itfaiye', 'polis' => '110', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'NL' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'FR' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'polis' => '17', 'ambulans' => '15', 'itfaiye' => '18', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'AT' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],

        /*
         * BE: 112'nin polisi de yönlendirdiği doğru, ama Belçika'nın KENDİ
         * resmî portalı (112.be) ikisini ayırıyor: 112 ambulans+itfaiye,
         * 101 acil polis. Dosyanın kendi kuralı gereği (genel'den farklıysa
         * yaz) 101 buraya aitti; Almanya'da 110 için yapılan ayrım burada
         * atlanmıştı.
         */
        'BE' => ['genel' => '112', 'genel_etiket' => 'Ambulans ve itfaiye', 'polis' => '101', 'not' => '', 'dogrulandi' => '2026-08-12'],

        'CH' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'polis' => '117', 'ambulans' => '144', 'itfaiye' => '118', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'SE' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],
        'NO' => ['genel' => '112', 'genel_etiket' => 'Polis', 'ambulans' => '113', 'itfaiye' => '110', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'DK' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],
        'IT' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],
        'ES' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],
        'PL' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],

        // --- Diğer Avrupa ---
        'GB' => ['genel' => '999', 'genel_etiket' => 'Tüm acil servisler', 'not' => '112 de çalışır.', 'dogrulandi' => '2026-08-11'],

        // --- Kuzey Amerika / Okyanusya ---
        'US' => ['genel' => '911', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],
        'CA' => ['genel' => '911', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],
        // 112 AU'da YALNIZ cepten çalışır; sabit hat ve uydu telefonundan çalışmaz.
        'AU' => ['genel' => '000', 'genel_etiket' => 'Tüm acil servisler', 'not' => 'Cep telefonundan 112 de çalışır.', 'dogrulandi' => '2026-08-11'],

        // --- Türk dünyası ---
        /*
         * DİKKAT: burası "Sovyet mirası" diye tek kalıptan doldurulmuştu ve
         * TM'de dördü de yanlış çıktı. Yeni ülke eklerken kalıbı kopyalama,
         * o ülkeyi ayrıca doğrula.
         *
         * AZ: 112 Fövqəladə Hallar Nazirliyi (afet/kurtarma) hattı; resmî
         * ifadeyle diğer servislere ULAŞILAMADIĞINDA kullanılır. Rakam yanlış
         * değil ama "her şeye ulaşır" sözünü vermiyor — bu yüzden etiketi
         * "Tüm acil servisler" DEĞİL, ve not bunu söylüyor.
         */
        'AZ' => ['genel' => '112', 'genel_etiket' => 'Afet, yangın ve kurtarma', 'polis' => '102', 'ambulans' => '103', 'itfaiye' => '101', 'not' => 'Genel hat afet, yangın ve kurtarma içindir; ayrıca sabit hattan yalnız Bakü, Sumqayıt ve Abşeron\'dan aranabiliyor. Polis, ambulans ve itfaiye için doğrudan aşağıdaki numaraları ara.', 'dogrulandi' => '2026-08-12'],
        'KZ' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'polis' => '102', 'ambulans' => '103', 'itfaiye' => '101', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'KG' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'polis' => '102', 'ambulans' => '103', 'itfaiye' => '101', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'UZ' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'polis' => '102', 'ambulans' => '103', 'itfaiye' => '101', 'not' => '', 'dogrulandi' => '2026-08-12'],

        /*
         * TM — DÜZELTİLDİ (2026-08-12). Önceki değerler (112/102/103/101)
         * bölgesel şablondan gelmişti ve DÖRDÜ DE YANLIŞTI.
         *
         * Türkmenistan üç haneli sisteme geçmedi ve 112'yi hiç almadı:
         * cepten 001 itfaiye / 002 polis / 003 ambulans, sabit hattan
         * 01 / 02 / 03. Birleşik bir numara olmadığı için `genel` alanına
         * ambulans konuldu — hayatî çağrıların çoğunluğu odur.
         *
         * BİRİNCİ DERECE KAYNAKLAR (iki bağımsız denetim turu):
         *  · turkmenistan.gov.tm (resmî devlet portalı, 16.03.2021):
         *    cumhurbaşkanı 112 ya da 911 gibi tek numaralı bir acil çağrı
         *    servisinin DENENMESİNİ ÖNERİYOR — yani o tarihte yoktu, ve
         *    hayata geçtiğine dair kayıt bulunamadı.
         *  · GOV.UK (İngiltere FCDO, Türkmenistan seyahat tavsiyesi):
         *    sabit hattan 03, cepten 003 arayıp ambulans isteyin.
         *  · travel.state.gov (ABD Dışişleri): acil servis için 03.
         *  · atavatan-turkmenistan.com (ülke içi yayın) sabit/cep ayrımını
         *    sütun sütun veriyor: 01/001, 02/002, 03/003, 04/004.
         * Ayrıca 112'nin geçerli olduğu ülkeler listesinde TM yok (AZ/KZ/KG/
         * UZ/RU var).
         *
         * BİLİNÇLİ RİSK: `genel` cep telefonu biçimi (003). Sabit hattan
         * doğrusu 03 ve düğme bunu bilmiyor; not söylüyor. Mobil öncelikli
         * bir site için doğru varsayılan kabul edildi.
         */
        'TM' => ['genel' => '003', 'genel_etiket' => 'Ambulans', 'polis' => '002', 'itfaiye' => '001', 'not' => 'Türkmenistan\'da tek acil numara yok ve 112 çalışmaz. Bu numaralar cep telefonu içindir; sabit hattan ararken baştaki sıfırlardan biri düşer.', 'dogrulandi' => '2026-08-12'],

        'RU' => ['genel' => '112', 'genel_etiket' => 'Tüm acil servisler', 'polis' => '102', 'ambulans' => '103', 'itfaiye' => '101', 'not' => '', 'dogrulandi' => '2026-08-12'],

        // --- Körfez ---
        /*
         * DİKKAT — BAE İLE SUUDİ ARABİSTAN'IN 997/998'İ BİRBİRİNİN TERSİ.
         * Bu bir kopyala-yapıştır hatası DEĞİL, iki ülke gerçekten farklı:
         *   AE: 998 ambulans, 997 sivil savunma (kaynak: u.ae, resmî devlet
         *       platformu — Police 999, Ambulance 998, Civil Defence 997)
         *   SA: 997 Kızılay/ambulans, 998 sivil savunma (kaynak: cst.gov.sa
         *       Ulusal Numaralandırma Planı)
         * Birini diğerine benzetmeye çalışan, yangında ambulans çağırtır.
         */
        'AE' => ['genel' => '999', 'genel_etiket' => 'Polis', 'ambulans' => '998', 'itfaiye' => '997', 'not' => '', 'dogrulandi' => '2026-08-12'],
        'QA' => ['genel' => '999', 'genel_etiket' => 'Tüm acil servisler', 'not' => '', 'dogrulandi' => '2026-08-11'],

        /*
         * SA — `genel` 911'DEN 999'A ÇEKİLDİ (2026-08-12).
         *
         * 911 Suudi Arabistan'da birleşik numara olarak tanıtılıyor ama
         * çağrı merkezleri yalnız DÖRT bölgede kurulu (Riyad, Mekke, Medine,
         * Doğu Bölgesi). Kalan dokuz bölgedeki kullanıcı en üstteki büyük
         * kırmızı düğmeye basıyor ve cevap alamayabiliyordu. 999 (polis)
         * ülke geneli. Resmî Ulusal Numaralandırma Planı (cst.gov.sa):
         * 999 polis, 998 sivil savunma, 997 Kızılay.
         */
        'SA' => ['genel' => '999', 'genel_etiket' => 'Polis', 'ambulans' => '997', 'itfaiye' => '998', 'not' => '911 birleşik hattı yalnız Riyad, Mekke, Medine ve Doğu Bölgesi\'nde cevap veriyor.', 'dogrulandi' => '2026-08-12'],
    ],
];
