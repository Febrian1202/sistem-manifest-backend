@extends('errors.layout')

@section('title', 'Kesalahan Server')

@section('icon')
    <i class="fa-solid fa-server text-4xl"></i>
@endsection

@section('code', '500')

@section('headline', 'Terjadi Kesalahan Server')

@section('message')
    Terjadi kesalahan internal pada server kami saat memproses permintaan Anda. Kami sedang berupaya memperbaikinya secepat mungkin. Silakan coba kembali beberapa saat lagi.
@endsection
