<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
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
        <a class="brand-link text-decoration-none" href="{{ route('dashboard') }}" title="{{ $siteName }}">
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="brand-image shadow-sm" onerror="this.style.display='none'">
            <span class="brand-text fw-semibold text-truncate">{{ $siteName }}</span>
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
