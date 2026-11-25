@extends('JangKeyte::layout.app')

@section('heading', __('User Detail'))

@section('button')
    <a href="{{ route('admin_user') }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left"></i> {{ __('View All') }}
    </a>
    @can('edit-user')
        <a href="{{ route('user.update', $user->id) }}" class="btn btn-primary btn-sm ms-1">
            <i class="bi bi-pencil-square"></i> {{ __('Edit') }}
        </a>
    @endcan
@endsection

@section('main_content')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <x-jangkeyte::htmls.image :name="$user->photo" object="user" :alt="$user->name" class="rounded-circle border" style="width:96px;height:96px;object-fit:cover;" />
                    </div>
                    <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    <div>
                        @foreach($user->roles as $role)
                            <span class="badge bg-{{ $role->code ?: 'primary' }}">{{ $role->name }}</span>
                        @endforeach
                    </div>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">UID</span>
                        <code>{{ $user->uid }}</code>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">{{ __('Username') }}</span>
                        <span>{{ $user->username }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">{{ __('Created At') }}</span>
                        <span>{{ $user->created_at?->format('Y-m-d H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header fw-semibold">{{ __('Effective Permissions') }}</div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($groupedPermissions as $group => $perms)
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="fw-semibold small border-bottom pb-1 mb-1">{{ $group }}</div>
                                    @foreach($perms as $perm)
                                        @if($user->hasPermissionTo($perm))
                                            <span class="badge text-bg-success me-1 mb-1">{{ $perm->name }}</span>
                                        @else
                                            <span class="badge text-bg-light text-muted border me-1 mb-1">{{ $perm->name }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
