@extends('JangKeyte::layout.app')

@section('heading', 'Roles')

@section('button')
<a href="{{ route('admin_role_create') }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-plus"></i> {{ __('Add New') }}</a>
@endsection

@section('main_content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="">                
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Role') }}</th>
                                    <th>{{ __('Slug') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $item)
                                <tr>
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->slug }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->code }}</td>
                                    <td class="py-1">
                                        <a href="{{ route('admin_role_delete', $item->id) }}" class="btn btn-danger btn-sm" onClick="return confirm('Are you sure?');">Delete</a>
                                        <a href="{{ route('admin_role_edit', $item->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection