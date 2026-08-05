@extends('errors.layout')

@section('kod', '404')
@section('baslik', 'Bu sayfa yok')
@section('aciklama', 'Aradığın sayfa taşınmış, kaldırılmış ya da adres yanlış yazılmış olabilir.')

@section('eylemler')
            <a class="dugme birincil" href="/">Ana sayfaya dön</a>
            <a class="dugme" href="/ilanlar">İlanlara göz at</a>
@endsection
