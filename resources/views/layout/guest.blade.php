<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = setting('site.name', config('app.name', 'System Manager'));
        $faviconVal = setting('site.favicon');
        $uploadPrefix = trim(config('jangkeyte.uploads.public_prefix', 'storage/uploads'), '/');
        $faviconUrl = $faviconVal
            ? (str_starts_with($faviconVal, 'http') || str_starts_with($faviconVal, '/')
                ? $faviconVal
                : asset($uploadPrefix . '/' . ltrim($faviconVal, '/')))
            : asset('assets/images/logo.png');
    @endphp
    <title>@yield('heading', $siteName) — {{ $siteName }}</title>
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    @include('JangKeyte::layout.partials.styles')
</head>
<body>
    <div id="app">
        @yield('main_header')
        <div class="main-wrapper">
            <section class="section">
                @yield('main_content')
            </section>
        </div>
        @yield('main_footer')
    </div>
    @include('JangKeyte::layout.partials.scripts')
</body>
</html>
