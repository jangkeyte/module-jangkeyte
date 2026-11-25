@extends('JangKeyte::layout.app')

@section('heading', $user->exists ? __('Edit User') : __('Create User'))

@section('button')
    <a href="{{ route('admin_user') }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left"></i> {{ __('View All') }}
    </a>
@endsection

@section('main_content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $user->exists ? route('update.user', $user->id) : route('create.user') }}" enctype="multipart/form-data">
                @csrf
                @if($user->exists)
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $user->id }}">
                @endif

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ __('Name') }} *</label>
                    <div class="col-sm-10">
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ __('Username') }}</label>
                    <div class="col-sm-10">
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control @error('username') is-invalid @enderror" placeholder="{{ __('Defaults to email if left blank') }}">
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ __('Email') }} *</label>
                    <div class="col-sm-10">
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ __('Password') }} {{ $user->exists ? '' : '*' }}</label>
                    <div class="col-sm-10">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $user->exists ? '' : 'required' }} placeholder="{{ $user->exists ? __('Leave blank to keep current password') : '' }}">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ __('Role') }}</label>
                    <div class="col-sm-10">
                        @php $currentRoleId = old('role', $user->roles->first()?->id); @endphp
                        <select name="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="">-- {{ __('Select Role') }} --</option>
                            @foreach($roles as $roleId => $roleName)
                                <option value="{{ $roleId }}" @selected((int) $currentRoleId === (int) $roleId)>{{ $roleName }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label">{{ __('Avatar') }}</label>
                    <div class="col-sm-10">
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary">{{ $user->exists ? __('Update') : __('Create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
