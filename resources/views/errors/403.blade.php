@extends('errors.layout')

@section('title', 'Akses Ditolak')

@section('icon')
    <i class="fa-solid fa-shield-halved text-4xl"></i>
@endsection

@section('code', '403')

@section('headline', 'Akses Tidak Diizinkan')

@section('message')
    Maaf, Anda tidak memiliki hak akses atau izin yang diperlukan untuk mengakses halaman ini. Silakan kembali ke beranda atau masuk menggunakan akun lain yang memiliki izin.
@endsection
