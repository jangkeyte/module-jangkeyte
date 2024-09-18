@extends('JangKeyte::layout.app')

@section('heading', __('Create Role'))

@section('button')
<a href="{{ route('admin_role') }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-folder-check"></i> {{ __('View All') }}</a>
@endsection

@section('main_content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin_role_store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Name *</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>Slug *</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug') }}">
                        </div>
                        <div class="form-group mb-3">
                            <label>Description *</label>
                            <textarea name="description" class="form-control editor" cols="30" rows="5">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>Code</label>
                            <input type="text" class="form-control" name="code" value="{{ old('code') }}">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <div class="card card-danger">
                                <div class="card-header">
                                    <h3 class="card-title">Quản lý quyền hạn</h3>
                                </div>
                                <div class="card-body">                                
                                    <div class="form-group d-flex text-center align-content-start flex-wrap clearfix">
                                        <div class="group-title p-2 text-start">
                                            <h4>{{ __('Đối tượng') }}</h4>
                                        </div>                            
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Browse') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Read') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Add') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Edit') }}</div>
                                        <div class="icheck-success p-2" style="width:15%">{{ __('Delete') }}</div>
                                    </div>
                                    
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
                                            <input type="checkbox" id="permission-{{ $permission->slug }}" name="permission[]" value="{{ $permission->slug }}">
                                            <label for="permission-{{ $permission->slug }}"></label>
                                        </div>
                                        @php $current_group = $permission->group @endphp
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection