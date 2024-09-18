@extends('JangKeyte::layout.app')

@section('heading', 'Tạo mới người dùng')

@section('main_content')

<div class="container">
    <div class="row mt-3">
        <div class="col-md-12">            
            {{ html()->form('POST')->route('user.create')->acceptsFiles()->open() }}
            <div class="row mt-3">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6 form-floating mb-3">
                            <x-authetication::forms.text name="name" label="{{ __('Name') }}" :value="old('name')" />
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <x-authetication::forms.text name="email" label="{{ __('Email') }}" :value="old('email')" />
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <x-authetication::forms.password name="password" label="{{ __(' Password') }}" :value="old('password')" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <x-authetication::forms.image name="image" label="{{ __('Image') }}" :value="old('image')" />
                        </div>
                        @if (auth()->user()->hasRole('admin', 'administrator', 'quan-tri-vien-toi-cao', 'quan-tri-vien'))
                            <!-- Khu vực quản lý phân quyền -->
                            <div class="col-md-6 form-floating mb-3">
                                <x-authetication::forms.select name="role" label="{{ __('Role') }}" :options="$roles" />
                            </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-md-12 my-3 text-center">
                            <div class="form-group">
                                <x-authetication::forms.button text="{{ __('Save') }}" icon="fa fa-save" class="btn btn-sm btn-success" />
                                <a href="{{ route('admin_user') }}" class="btn btn-sm btn-danger">{{ __('Cancel') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="col-12 text-center">

                    </div>
                </div>
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>
</div>

@stop
