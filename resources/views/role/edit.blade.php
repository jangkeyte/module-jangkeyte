@extends('JangKeyte::layout.app')

@section('heading', 'Edit Posts')

@section('button')
<a href="{{ route('admin_role') }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-folder-check"></i> {{ __('View All') }}</a>
@endsection

@section('main_content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin_role_update', $role_single->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label>{{ __('Name') }} *</label>
                            <input type="text" class="form-control" name="name" value="{{ $role_single->name }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>{{ __('Slug') }} *</label>
                            <input type="text" class="form-control" name="slug" value="{{ $role_single->slug }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>{{ __('Description') }}</label>
                            <textarea name="description" class="form-control editor" cols="30" rows="5">{{ $role_single->description }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>{{ __('Code') }} </label>
                            <input type="text" class="form-control" name="code" value="{{ $role_single->code }}">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="card card-danger">
                                <div class="card-header">
                                    <h3 class="card-title">{{ __('Permission Manager') }}</h3>
                                </div>
                                <div class="card-body">                                
                                    <div class="form-group d-flex text-center align-content-start flex-wrap clearfix">
                                        <div class="group-title p-2 text-start">
                                            <h4>{{ __('Object') }}</h4>
                                        </div>                            
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Browse') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Read') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Add') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Edit') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Delete') }}</div>
                                    </div>
                                    @php $user_permissions = $role_single->permissions->pluck('slug')->toArray() @endphp
                                    @php $current_group = '' @endphp
                                    <div class="form-group d-flex text-center align-content-start flex-wrap clearfix">
                                    @foreach($permissions as $permission)
                                        @if ( $current_group != $permission->group )
                                            </div>
                                            <div class="form-group d-flex text-center align-content-start flex-wrap clearfix">
                                                <div class="group-title p-2 text-start">
                                                    {{ $permission->group }}
                                                </div>
                                        @endif
                                        <div class="icheck-success p-2" style="width:15%">
                                            <input type="checkbox" id="permission-{{ $permission->slug }}" name="permission[]" value="{{ $permission->slug }}" @if(in_array($permission->slug, $user_permissions)) checked="" @endif >
                                            <label for="permission-{{ $permission->slug }}"></label>
                                        </div>
                                        @php $current_group = $permission->group @endphp
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection