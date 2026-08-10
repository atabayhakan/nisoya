{{--
    Ay-yıldız — Nisoya'nın acil yardım kimliği.

    NEDEN BU MOTİF: Nisoya'nın kitlesi yurt dışındaki Türkler ve acil düğmesi
    "bulunduğun ülkedeki Türklere/temsilciliğe hızlı ulaş" demek. Genel bir
    cankurtaran simidi ikonu bunu anlatmıyordu; ay-yıldız tek bakışta anlatıyor.

    NEDEN KIZILAY DEĞİL: kırmızı zemin üzerinde YALNIZ hilal, Kızılay'ın
    işaretidir ve bir acil yardım düğmesinde kullanılırsa o kurumla bağlantı
    ima eder. Bu yüzden hilal TEK BAŞINA kullanılmaz — yanında yıldız var,
    yani bayrak motifi; ulusal bir sembol, bir kurumun logosu değil.

    Hilal iki dairenin `evenodd` ile çıkarılmasıyla çiziliyor (maske yok:
    maske `id` gerektirir, aynı ikon sayfada iki kez basıldığında `id`
    çakışması sessiz render hatası yapardı).
--}}
<svg {{ $attributes->merge(['class' => 'h-4 w-4']) }}
     viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path fill-rule="evenodd"
          d="M4 12a7 7 0 1 1 14 0 7 7 0 1 1 -14 0z
             M7.6 12a5.6 5.6 0 1 1 11.2 0 5.6 5.6 0 1 1 -11.2 0z" />
    <polygon points="17.6,9.2 18.26,11.09 20.26,11.14 18.67,12.35 19.25,14.27 17.6,13.12 15.95,14.27 16.54,12.35 14.94,11.14 16.94,11.09" />
</svg>
