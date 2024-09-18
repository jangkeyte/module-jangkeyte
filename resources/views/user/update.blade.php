@extends('JangKeyte::layout.app')

@section('heading', 'Dashboard')

@section('main_content')

<div class="container">
    <div class="row">
        <div class="col-md-12">            
            @isset($user)
            {{ html()->form('POST')->route('update.user', 1)->acceptsFiles()->open() }}
            <div class="row mt-3">
                <div class="col-md-8">
                    <x-authetication::forms.hidden name="id" label="" :value="$user->id" />
                    <div class="row">
                        <div class="col-md-12 form-floating mb-3">
                            <x-authetication::forms.text name="uid" label="{{ __('Uid') }}" :value="$user->uid" disabled="disabled" />
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <x-authetication::forms.text name="name" label="{{ __('Name') }}" :value="$user->name" />
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <x-authetication::forms.text name="email" label="{{ __('Email') }}" :value="$user->email" />
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <x-authetication::forms.password name="password" label="{{ __('Password') }}" :value="$user->password" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <x-authetication::forms.image name="image" label="{{ __('Image') }}" :value="$user->photo" />
                        </div>

                        @if (auth()->user()->hasRole('admin', 'administrator'))
                            <!-- Khu vực quản lý phân quyền -->
                            <div class="col-md-6 form-floating mb-3">
                                <x-authetication::forms.text name="username" label="{{ __('Username') }}" :value="$user->username" />
                            </div>
                            <div class="col-md-6 form-floating mb-3">
                                <x-authetication::forms.select name="role" label="{{ __('Role') }}" :options="$roles" :value="$user->roles[0]->id" />
                            </div>
                        @endif
                    </div>
                    <div class="row">
                        <div class="col-md-12 my-3 text-center">
                            <div class="form-group">
                                <x-authetication::forms.button text="{{ __('Save') }}" icon="fa fa-save"
                                    class="btn btn-sm btn-success" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="col-12 text-center">
                        @if (file_exists(public_path('/storage/uploads/users/' . $user->photo)))
                            <img id="demo" src="{!! asset('/storage/uploads/users/' . $user->photo) !!}" class="img-thumbnail rounded-circle w-75">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" height="auto" width="50%"
                                viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.-->
                                <path
                                    d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z" />
                            </svg>
                        @endif
                    </div>
                </div>
            </div>
            {{ html()->form()->close() }}
            @else
                <div><h2>Không tồn tại user này</h2></div>
            @endisset

        </div>
    </div>
</div>

@stop
