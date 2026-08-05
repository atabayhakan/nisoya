@extends('errors.layout')

@section('kod', 'HATA')
@section('baslik', 'Bir şeyler ters gitti')
{{-- "ve kaydedildi" ÇIKARILDI (2026-08-05).

     İlk yazışta "Bu bizim tarafımızdaki bir sorun ve kaydedildi" diyordu. Aynı
     gün El Kitabı canlıda 500 verdi ve `storage/logs` BOMBOŞTU — yani o cümle
     ziyaretçiye doğrulayamadığımız bir şey söylüyordu.

     Sitedeki her bilginin gerçek olması kuralı, hata sayfası için de geçerli.
     Kaydın gerçekten tutulup tutulmadığı artık panelden görülebiliyor
     (Sistem & Araçlar → Son Hatalar); orada "tutuluyor" görüldüğü gün bu
     cümle geri gelebilir. --}}
@section('aciklama', 'Bu bizim tarafımızdaki bir sorun, senin yaptığın bir şeyden kaynaklanmıyor. Birkaç dakika sonra tekrar denemeni öneririz.')

@section('eylemler')
            <a class="dugme birincil" href="/">Ana sayfaya dön</a>
@endsection
