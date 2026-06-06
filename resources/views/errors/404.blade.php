@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan')

@section('icon')
    <i class="fa-solid fa-compass text-4xl text-primary"></i>
@endsection

@section('code', '404')

@section('headline', 'Halaman Tidak Ditemukan')

@section('message')
    Halaman yang Anda tuju tidak ditemukan, telah dipindahkan, atau alamat URL yang Anda masukkan salah. Pastikan kembali alamat yang Anda ketik sudah benar.
@endsection
