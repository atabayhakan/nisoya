{{--
    LOGO İKONU — YALNIZCA GÖRÜNÜRLÜK KAPISI (2026-08-19, sahibin isteği).

    Marka yazısının solundaki kare rozeti (yüklenmiş logo görseli ya da
    varsayılan <x-logo-mark>) gizlemeyi/göstermeyi kararlaştırır. İkonun
    KENDİ görünümüne (renk, gradyan, boyut) hiç karışmaz — klasik ve Vitrin
    header'ları kendi markup'larını slot olarak aynen geçirir, çünkü ikisi
    farklı stiller kullanıyor (klasik düz zümrüt, Vitrin gradyan) ve bunları
    tek bir bileşende birleştirmek gereksiz bir soyutlama olurdu.

    Amaç: mobilde başlıkta sağdaki ülke/Acil/Üye ol kümesine yer açmak.
--}}
@unless (\App\Support\TemaJetonlari::logoIkonuGizliMi())
    {{ $slot }}
@endunless
