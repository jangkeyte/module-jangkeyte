@extends('errors.layouts')

@section('code', '500')
@section('title', __('Lỗi'))

@section('image')
    <div style="background-image: url({{ asset('/svg/500.svg') }});" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
    </div>
@endsection

@section('message', __('Rất tiếc, đã xảy ra lỗi trên máy chủ của chúng tôi.'))