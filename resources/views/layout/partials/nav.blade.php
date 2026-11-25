<ul class="navbar-nav">
    <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list fs-5"></i></a>
    </li>
    <li class="nav-item d-none d-md-block">
        <a href="{{ url('/') }}" class="nav-link fw-medium">{{ __('Home') }}</a>
    </li>
</ul>

<ul class="navbar-nav ms-auto align-items-center gap-1">
    @if(app()->getLocale() === 'vi')
        <li class="nav-item">
            <a href="{{ route('switch_language', ['en']) }}" class="nav-link px-2" title="Switch to English">
                <img src="{{ asset('assets/images/united-kingdom.svg') }}" alt="EN" class="rounded-1 shadow-sm" style="width:22px;">
            </a>
        </li>
    @else
        <li class="nav-item">
            <a href="{{ route('switch_language', ['vi']) }}" class="nav-link px-2" title="Chuyển sang Tiếng Việt">
                <img src="{{ asset('assets/images/vietnam.svg') }}" alt="VI" class="rounded-1 shadow-sm" style="width:22px;">
            </a>
        </li>
    @endif

    <li class="nav-item">
        <a class="nav-link px-2" href="#" data-lte-toggle="fullscreen" title="{{ __('Fullscreen') }}">
            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none;"></i>
        </a>
    </li>

    @auth
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <x-jangkeyte::htmls.image
                    :name="auth()->user()->photo"
                    object="user"
                    alt="Avatar"
                    class="user-image rounded-circle shadow-sm"
                    style="width:32px;height:32px;object-fit:cover;font-size:0.95rem;"
                />
                <span class="d-none d-md-inline fw-medium text-dark">{{ auth()->user()->name }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                    <x-jangkeyte::htmls.image
                        :name="auth()->user()->photo"
                        object="user"
                        alt="Avatar"
                        class="user-image rounded-circle shadow"
                        style="width:60px;height:60px;object-fit:cover;font-size:1.65rem;"
                    />
                    <p>
                        {{ auth()->user()->name }}
                        <small>{{ auth()->user()->email }}</small>
                    </p>
                </li>
                <li class="user-footer">
                    <a href="{{ route('admin_profile') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-person-badge me-1"></i>{{ __('Profile') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm px-3">
                            <i class="bi bi-box-arrow-right me-1"></i>{{ __('Sign out') }}
                        </button>
                    </form>
                </li>
            </ul>
        </li>
    @endauth
</ul>
