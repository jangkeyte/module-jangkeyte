<ul class="navbar-nav">
    <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
    </li>
    <li class="nav-item d-none d-md-block">
        <a href="{{ url('/') }}" class="nav-link">{{ __('Home') }}</a>
    </li>
</ul>

<ul class="navbar-nav ms-auto align-items-center">
    @if(app()->getLocale() === 'vi')
        <li class="nav-item">
            <a href="{{ route('switch_language', ['en']) }}" class="nav-link" title="English">
                <img src="{{ asset('assets/images/united-kingdom.svg') }}" alt="EN" style="width:22px;">
            </a>
        </li>
    @else
        <li class="nav-item">
            <a href="{{ route('switch_language', ['vi']) }}" class="nav-link" title="Tiếng Việt">
                <img src="{{ asset('assets/images/vietnam.svg') }}" alt="VI" style="width:22px;">
            </a>
        </li>
    @endif

    <li class="nav-item">
        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none;"></i>
        </a>
    </li>

    @auth
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <x-jangkeyte::htmls.image :name="auth()->user()->photo" object="user" alt="Avatar" class="user-image rounded-circle shadow-sm" />
                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                    <x-jangkeyte::htmls.image :name="auth()->user()->photo" object="user" alt="Avatar" class="user-image rounded-circle shadow" />
                    <p>
                        {{ auth()->user()->name }}
                        <small>{{ auth()->user()->email }}</small>
                    </p>
                </li>
                <li class="user-footer d-flex justify-content-between">
                    <a href="{{ route('admin_profile') }}" class="btn btn-outline-secondary btn-sm">{{ __('Profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Sign out') }}</button>
                    </form>
                </li>
            </ul>
        </li>
    @endauth
</ul>
