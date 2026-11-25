<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = setting('site.name', config('app.name', 'JangKeyte Admin'));
        $faviconVal = setting('site.favicon');
        $uploadPrefix = trim(config('jangkeyte.uploads.public_prefix', 'storage/uploads'), '/');
        $faviconUrl = $faviconVal
            ? (str_starts_with($faviconVal, 'http') || str_starts_with($faviconVal, '/')
                ? $faviconVal
                : asset($uploadPrefix . '/' . ltrim($faviconVal, '/')))
            : asset('assets/images/logo.png');
    @endphp
    <title>@yield('heading', __('Dashboard')) — {{ $siteName }}</title>
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    @include('JangKeyte::layout.partials.styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        @include('JangKeyte::layout.partials.header')
        @include('JangKeyte::layout.partials.sidebar')

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0">@yield('heading') @yield('button')</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item active">@yield('heading')</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @yield('main_content')
                </div>
            </div>
        </main>

        @include('JangKeyte::layout.partials.footer')
    </div>
</body>
</html>
