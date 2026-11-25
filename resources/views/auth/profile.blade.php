@extends('JangKeyte::layout.app')

@section('heading', __('Edit Profile'))

@section('main_content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin_profile_submit') }}" enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="name">{{ __('Name') }}</label>
                    <div class="col-sm-10">
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="email">{{ __('Email') }}</label>
                    <div class="col-sm-10">
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="password">{{ __('New Password') }}</label>
                    <div class="col-sm-10">
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('Leave blank to keep current password') }}">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
                    <div class="col-sm-10">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label" for="photo">{{ __('Avatar') }}</label>
                    <div class="col-sm-10">
                        <input type="file" id="photo" name="photo" class="form-control @error('photo') is-invalid @enderror">
                        @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
