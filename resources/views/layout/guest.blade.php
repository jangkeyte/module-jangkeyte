<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">

        <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
        
        <title>{{ env('APP_NAME') ?? __('System Manager') }}</title>

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