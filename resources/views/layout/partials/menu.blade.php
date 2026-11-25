@php
    $menuItems = app(\JangKeyte\Support\MenuRegistry::class)->all();
@endphp

@foreach($menuItems as $item)
    @php
        $canViewItem = empty($item['permission']) || (auth()->check() && auth()->user()->can($item['permission']));
        if (! $canViewItem) {
            continue;
        }

        $patterns = (array) ($item['active_patterns'] ?? []);
        $isOpen = false;
        foreach ($patterns as $pattern) {
            if (request()->is($pattern)) {
                $isOpen = true;
                break;
            }
        }

        $visibleChildren = [];
        foreach (($item['children'] ?? []) as $child) {
            $canViewChild = empty($child['permission']) || (auth()->check() && auth()->user()->can($child['permission']));
            if ($canViewChild && (empty($child['route']) || app('router')->has($child['route']))) {
                $visibleChildren[] = $child;
            }
        }
    @endphp

    @if(!empty($visibleChildren))
        <li class="nav-item {{ $isOpen ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $isOpen ? 'active' : '' }}">
                <i class="nav-icon {{ $item['icon'] ?? 'bi bi-circle' }}"></i>
                <p>
                    {{ __($item['label']) }}
                    <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                @foreach($visibleChildren as $child)
                    @php
                        $childActive = false;
                        foreach ((array) ($child['active_patterns'] ?? []) as $cp) {
                            if (request()->is($cp)) {
                                $childActive = true;
                                break;
                            }
                        }
                        if (! $childActive && ! empty($child['route']) && request()->routeIs($child['route'])) {
                            $childActive = true;
                        }
                    @endphp
                    <li class="nav-item">
                        <a href="{{ !empty($child['route']) ? route($child['route']) : ($child['url'] ?? '#') }}" class="nav-link {{ $childActive ? 'active' : '' }}">
                            <i class="nav-icon bi bi-caret-right{{ $childActive ? '-fill' : '' }}"></i>
                            <p>{{ __($child['label']) }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
    @elseif(!empty($item['route']) && app('router')->has($item['route']))
        <li class="nav-item">
            <a href="{{ route($item['route']) }}" class="nav-link {{ $isOpen || request()->routeIs($item['route']) ? 'active' : '' }}">
                <i class="nav-icon {{ $item['icon'] ?? 'bi bi-circle' }}"></i>
                <p>{{ __($item['label']) }}</p>
            </a>
        </li>
    @endif
@endforeach

@stack('menus')
