@extends('JangKeyte::layout.app')

@section('heading', __('Users'))

@section('button')
    @can('add-user')
        <a href="{{ route('user.create') }}" class="btn btn-primary btn-sm ms-2">
            <i class="bi bi-plus-lg"></i> {{ __('Add New') }}
        </a>
    @endcan
@endsection

@section('main_content')
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin_user') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="search" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="{{ __('Search by name, username, email...') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-search"></i> {{ __('Search') }}
                    </button>
                    @if(request()->filled('keyword'))
                        <a href="{{ route('admin_user') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th style="width:64px;">{{ __('Avatar') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Username') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Roles') }}</th>
                            <th class="text-end" style="width:210px;">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                <td>
                                    <x-jangkeyte::htmls.image :name="$user->photo" object="user" :alt="$user->name" class="rounded-circle border" />
                                </td>
                                <td class="fw-semibold">
                                    <a href="{{ route('user.detail', $user->id) }}" class="text-decoration-none">{{ $user->name }}</a>
                                </td>
                                <td><code>{{ $user->username }}</code></td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-{{ $role->code ?: 'secondary' }}">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    @can('read-user')
                                        <a href="{{ route('user.detail', $user->id) }}" class="btn btn-outline-secondary btn-sm">{{ __('View') }}</a>
                                    @endcan
                                    @can('edit-user')
                                        <a href="{{ route('user.update', $user->id) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                    @endcan
                                    @if((int) auth()->id() !== (int) $user->id)
                                        @can('delete-user')
                                            <form method="POST" action="{{ route('user.remove', $user->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ __('No users found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
