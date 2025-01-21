<li class="nav-item  {{ Request::is('admin/dashboard') ? 'menu-open' : '' }}">
    <a href="{{ route('dashboard') }}" class="nav-link" {{ Request::is('admin/dashboard') ? 'active' : '' }}>
        <i class="nav-icon bi bi-speedometer"></i> <p> {{ __('Dashboard') }} </p>
    </a>
</li>

@include('TripBooking::layout.partials.menu')
{{-- @stack('menus') --}}

<!-- USERS -->
{{-- @can('browse-user') --}}
<li class="nav-item {{ Request::is('admin/user/*') || Request::is('admin/role') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ Request::is('admin/user') || Request::is('admin/role') ? 'active' : '' }}">
        <i class="bi bi-person-gear"></i>
        <p>
            {{ __('Users') }}
            <i class="nav-arrow bi bi-chevron-right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item"><a class="nav-link {{ Request::is('admin/user') ? 'active' : '' }}" href="{{ route('admin_user') }}"><i class="nav-icon bi bi-caret-right{{ Request::is('admin/user') ? '-fill' : '' }}"></i> {{ __('All Users') }}</a></li>
        {{-- @can('edit-role') --}}
        <li class="nav-item"><a class="nav-link {{ Request::is('admin/role') ? 'active' : '' }}" href="{{ route('admin_role') }}"><i class="nav-icon bi bi-caret-right{{ Request::is('admin/role') ? '-fill' : '' }}"></i> {{ __('Roles') }}</a></li>
        {{-- @endcan --}}
    </ul>
</li>
{{-- @endcan --}}