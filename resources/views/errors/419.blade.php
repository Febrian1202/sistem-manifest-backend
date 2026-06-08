@extends('errors.layout')

@section('title', 'Sesi Berakhir')

@section('icon')
    <i class="fa-solid fa-clock-rotate-left text-4xl"></i>
@endsection

@section('code', '419')

@section('headline', 'Sesi Telah Berakhir')

@section('message')
    Maaf, halaman telah kedaluwarsa atau sesi Anda telah berakhir karena tidak ada aktivitas dalam waktu yang lama. Silakan kembali ke beranda atau muat ulang halaman untuk melanjutkan.
@endsection
