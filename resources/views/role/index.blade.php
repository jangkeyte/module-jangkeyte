@extends('JangKeyte::layout.app')

@section('heading', __('Roles'))

@section('button')
    @can('add-role')
        <a href="{{ route('admin_role_create') }}" class="btn btn-primary btn-sm ms-2">
            <i class="bi bi-plus-lg"></i> {{ __('Add New') }}
        </a>
    @endcan
@endsection

@section('main_content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Slug') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Permissions') }}</th>
                            <th class="text-end" style="width:180px;">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge bg-{{ $item->code ?: 'secondary' }}">{{ $item->name }}</span></td>
                                <td><code>{{ $item->slug }}</code></td>
                                <td>{{ $item->description }}</td>
                                <td><span class="badge text-bg-info">{{ $item->permissions_count ?? 0 }}</span></td>
                                <td class="text-end">
                                    @can('edit-role')
                                        <a href="{{ route('admin_role_edit', $item->id) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                    @endcan
                                    @if(!in_array($item->slug, ['administrator', 'admin'], true))
                                        @can('delete-role')
                                            <form method="POST" action="{{ route('admin_role_delete', $item->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
