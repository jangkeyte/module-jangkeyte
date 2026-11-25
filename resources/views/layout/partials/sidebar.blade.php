<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a class="brand-link text-decoration-none" href="{{ route('dashboard') }}">
            @php
                $siteName = setting('site.name', config('app.name', 'JangKeyte'));
                $logoVal = setting('site.logo');
                $uploadPrefix = trim(config('jangkeyte.uploads.public_prefix', 'storage/uploads'), '/');
                $logoUrl = $logoVal
                    ? (str_starts_with($logoVal, 'http') || str_starts_with($logoVal, '/')
                        ? $logoVal
                        : asset($uploadPrefix . '/' . ltrim($logoVal, '/')))
                    : asset('assets/images/logo.png');
            @endphp
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="brand-image opacity-75 shadow" onerror="this.style.display='none'">
            <span class="brand-text fw-semibold">{{ $siteName }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @include('JangKeyte::layout.partials.menu')
            </ul>
        </nav>
    </div>
</aside>
