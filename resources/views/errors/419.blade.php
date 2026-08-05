@extends('errors.layout')

@section('kod', 'OTURUM')
@section('baslik', 'Sayfa zaman aşımına uğradı')
@section('aciklama', 'Güvenlik için formlar bir süre sonra geçersiz olur. Sayfayı yenileyip tekrar dene — yazdıkların kaybolduysa kusura bakma.')

@section('eylemler')
            <button class="dugme birincil" type="button" onclick="history.back()">Geri dön ve tekrar dene</button>
            <a class="dugme" href="/">Ana sayfa</a>
@endsection
