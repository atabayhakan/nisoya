@extends('errors.layout')

@section('kod', '403')
@section('baslik', 'Bu sayfaya erişimin yok')
@section('aciklama', 'Bu bölüm senin hesabına kapalı. Yanlış bağlantıya tıkladıysan ana sayfadan devam edebilirsin.')

@section('eylemler')
            <a class="dugme birincil" href="/">Ana sayfaya dön</a>
            <a class="dugme" href="/giris">Giriş yap</a>
@endsection
